<?php

namespace App\Http\Controllers;

use App\Models\DigitalDtrSignatureRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Helpers;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\DigitalSignatureResources\DigitalDtrSignatureRequestResource;
use App\Models\DigitalCertificate;
use App\Traits\DigitalDtrSignatureLoggable;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\DB;
use App\Services\DigitalSignatureService;
use App\Services\DtrSigningService;

class DigitalDtrSignatureRequestController extends Controller
{
    private string $CONTROLLER_NAME = 'DigitalDtrSignatureRequestController';
    protected $signatureService;
    protected $dtrSigningService;

    use DigitalDtrSignatureLoggable;

    public function __construct(
        DigitalSignatureService $signatureService,
        DtrSigningService $dtrSigningService
    ) {
        $this->signatureService = $signatureService;
        $this->dtrSigningService = $dtrSigningService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = DigitalDtrSignatureRequest::query();
            $query->with(['digitalDtrSignatureRequestFile']);

            if ($request->has('dtr_date')) {
                $query->where('dtr_date', $request->input('dtr_date'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $dtrs = $query->orderBy('created_at', 'desc')->paginate(10);

            if ($dtrs->isEmpty()) {
                return response()->json([
                    'message' => 'No digital signed DTR found',
                    'data' => []
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Retrieved all digital signed DTR',
                'data' => DigitalDtrSignatureRequestResource::collection($dtrs)
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    // public function show($id): BinaryFileResponse
    // {
    //     try {
    //         $digitalDtrSignatureRequest = DigitalDtrSignatureRequestFile::findOrFail($id);
    //         $filePath = storage_path('app/' . $digitalDtrSignatureRequest->file_path);

    //         if (!file_exists($filePath)) {
    //             return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
    //         }

    //         return response()->file($filePath, [
    //             'Content-Type' => Storage::mimeType($digitalDtrSignatureRequest->file_path),
    //             'Content-Disposition' => 'inline; filename="' . basename($digitalDtrSignatureRequest->file_path) . '"'
    //         ]);
    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         return response()->json(['message' => 'Digital DTR signature request file not found'], Response::HTTP_NOT_FOUND);
    //     } catch (\Throwable $th) {
    //         Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
    //         return response()->json(['message' => 'An error occurred while retrieving the file'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function show($id)
    {
        try {
            $digitalDtrSignatureRequest = DigitalDtrSignatureRequest::with('digitalDtrSignatureRequestFile')->findOrFail($id);

            return response()->json([
                'message' => 'Digital DTR signature request retrieved successfully',
                'data' => new DigitalDtrSignatureRequestResource($digitalDtrSignatureRequest)
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Digital DTR signature request not found'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => 'An error occurred while retrieving the digital DTR signature request'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approveSignatureRequest(Request $request)
    {
        try {
            $request->validate([
                'signature_request_id' => 'required|exists:digital_dtr_signature_requests,id',
                'approved' => 'required|boolean',
                'whole_month' => 'required|boolean',
                'remarks' => 'nullable|string|max:255',
            ]);

            $user = $request->user;
            $employee = EmployeeProfile::with('personalInformation')->where('id', $user->id)->first();
            $employee_name = $employee->personalInformation->employeeName();

            $signatureRequest = DigitalDtrSignatureRequest::where('id', $request->signature_request_id)
                ->where('employee_head_profile_id', $user->id)
                ->first();
            

            if (!$signatureRequest) {
                return response()->json(['message' => 'Digital DTR signature request not found'], Response::HTTP_NOT_FOUND);
            }

            $signatureRequest->status = $request->approved ? 'Approved' : 'Rejected';
            $signatureRequest->remarks = $request->remarks;
            $signatureRequest->approved_at = now();
            $signatureRequest->save();

            $this->logDtrSignatureAction(
                $signatureRequest->id,
                $user->id, // performed by
                $signatureRequest->status,
                'DTR Signature Request is ' . strtolower($signatureRequest->status) . ' by' . $employee_name,
            );

            $certificate = DigitalCertificate::with('digitalCertificateFile')->where('employee_profile_id', $signatureRequest->employee_profile_id)->first();
            if (!$certificate) {
                return response()->json(['message'=> 'Digital certificate not found'], Response::HTTP_NOT_FOUND);
            }

            $signDtrOwner = $this->dtrSigningService->processOwnerSigning($certificate, $signatureRequest, $user);

            return response()->json([
                'message' => 'Signature request ' . strtolower($signatureRequest->status) . ' successfully',
                'data' => new DigitalDtrSignatureRequestResource($signatureRequest)
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'approveSignatureRequest', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approveBatchSignatureRequests(Request $request)
    {
        try {
            $request->validate([
                'signature_request_ids' => 'required|array',
                'signature_request_ids.*' => 'required|exists:digital_dtr_signature_requests,id',
                'approved' => 'required|boolean',
                'remarks' => 'nullable|string|max:255',
            ]);

            $user = $request->user;
            $employee = EmployeeProfile::with('personalInformation')->where('id', $user->id)->first();
            $employee_name = $employee->personalInformation->employeeName();

            // Begin transaction
            DB::beginTransaction();

            try {
                $signatureRequests = DigitalDtrSignatureRequest::whereIn('id', $request->signature_request_ids)
                    ->where('employee_head_profile_id', $user->id)
                    ->where('status', '!=', 'Approved') // Prevent re-approval
                    ->where('status', '!=', 'Rejected') // Prevent changing rejected status
                    ->get();

                if ($signatureRequests->isEmpty()) {
                    return response()->json(['message' => 'No valid signature requests found'], Response::HTTP_NOT_FOUND);
                }

                $action = $request->approved ? 'Approved' : 'Rejected';
                $currentTime = now();

                foreach ($signatureRequests as $signatureRequest) {
                    // Update signature request
                    $signatureRequest->status = $action;
                    $signatureRequest->remarks = $request->remarks;
                    $signatureRequest->approved_at = $currentTime;
                    $signatureRequest->save();

                    // Log the action
                    $this->logDtrSignatureAction(
                        $signatureRequest->id,
                        $user->id,
                        $action,
                        "DTR Signature Request is {$action} by {$employee_name}"
                    );
                }

                DB::commit();

                return response()->json([
                    'message' => count($signatureRequests) . ' signature requests ' . strtolower($action) . ' successfully',
                    'data' => DigitalDtrSignatureRequestResource::collection($signatureRequests)
                ], Response::HTTP_OK);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'approveBatchSignatureRequests', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
