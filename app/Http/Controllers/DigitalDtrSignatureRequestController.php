<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
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
use Illuminate\Support\Facades\Storage;
use App\Models\Notifications;
use App\Models\UserNotifications;

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

            $user = $request->user;
            $query = DigitalDtrSignatureRequest::query();
            $query->with([
                'digitalDtrSignatureRequestFile',
                'employeeProfile.personalInformation',
                'employeeProfileHead.personalInformation'
            ])
                ->where('employee_head_profile_id', $user->id);


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

            $signature_request = DigitalDtrSignatureRequest::with('digitalDtrSignatureRequestFile')->where('id', $request->signature_request_id)
                ->where('employee_head_profile_id', $user->id)
                ->first();


            if (!$signature_request) {
                return response()->json(['message' => 'Digital DTR signature request not found'], Response::HTTP_NOT_FOUND);
            }

            $signature_request->status = $request->approved ? 'Approved' : 'Rejected';
            $signature_request->remarks = $request->remarks;
            $signature_request->approved_at = now();
            $signature_request->save();

            $this->logDtrSignatureAction(
                $signature_request->id,
                $user->id, // performed by
                $signature_request->status,
                'DTR Signature Request is ' . strtolower($signature_request->status) . ' by ' . $employee_name,
            );

            $certificate_owner = DigitalCertificate::with('digitalCertificateFile')->where('employee_profile_id', $signature_request->employee_profile_id)->first();
            if (!$certificate_owner) {
                return response()->json(['message' => 'Digital certificate not found'], Response::HTTP_NOT_FOUND);
            }

            $certificate_incharge = DigitalCertificate::with('digitalCertificateFile')->where('employee_profile_id', $signature_request->employee_head_profile_id)->first();
            if (!$certificate_incharge) {
                return response()->json(['message' => 'Digital certificate not found'], Response::HTTP_NOT_FOUND);
            }



            $disk_name = 'private';
            $file_path = $signature_request->digitalDtrSignatureRequestFile->file_path;

            if (!Storage::disk($disk_name)->exists($file_path)) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            $file_content = Storage::disk($disk_name)->get($file_path);
            $mime_type = Storage::disk($disk_name)->mimeType($file_path);
            $filename = basename($file_path);

            $temp_path = tempnam(sys_get_temp_dir(), 'uploaded_file_');
            file_put_contents($temp_path, $file_content);

            $uploaded_file = new \Illuminate\Http\UploadedFile(
                $temp_path,
                $filename,
                $mime_type,
                null,
                true
            );

            // SIGN DTR OWNER
            $this->dtrSigningService->processOwnerSigning($uploaded_file, $certificate_owner, $request->whole_month);
            // SIGN DTR INCHARGE
            $this->dtrSigningService->processInchargeSigning([$signature_request->id], $certificate_incharge, $request->whole_month);

            $notification = Notifications::create([
                'title' => $employee_name . ' has approved and signed your DTR',
                'description' => 'Approval of Digital DTR Signature Request',
                'module_path' => 'approve-dtr',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $certificate_owner->employee_profile_id,
            ]);

            Helpers::sendNotification([
                'id' => $certificate_owner->employee_profile_id,
                'data' => new NotificationResource($user_notification),
            ]);

            return response()->json([
                'message' => 'Signature request ' . strtolower($signature_request->status) . ' successfully',
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
                'signature_requests' => 'required|array',
                'signature_requests.*.id' => 'required|exists:digital_dtr_signature_requests,id',
                'signature_requests.*.approved' => 'required|boolean',
                'signature_requests.*.whole_month' => 'required|boolean',
                'signature_requests.*.remarks' => 'nullable|string|max:255',
            ]);

            $user = $request->user;
            $employee = EmployeeProfile::with('personalInformation')->where('id', $user->id)->first();
            $employee_name = $employee->personalInformation->employeeName();

            DB::beginTransaction();

            $results = [];

            foreach ($request->signature_requests as $request_data) {
                $signature_request = DigitalDtrSignatureRequest::with('digitalDtrSignatureRequestFile')
                    ->where('id', $request_data['id'])
                    ->where('employee_head_profile_id', $user->id)
                    ->first();

                if (!$signature_request) {
                    $results[] = ['id' => $request_data['id'], 'status' => 'error', 'message' => 'Signature request not found'];
                    continue;
                }

                $signature_request->status = $request_data['approved'] ? 'Approved' : 'Rejected';
                $signature_request->remarks = $request_data['remarks'];
                $signature_request->approved_at = now();
                $signature_request->save();

                $this->logDtrSignatureAction(
                    $signature_request->id,
                    $user->id,
                    $signature_request->status,
                    'DTR Signature Request is ' . strtolower($signature_request->status) . ' by ' . $employee_name
                );

                $certificate_owner = DigitalCertificate::with('digitalCertificateFile')->where('employee_profile_id', $signature_request->employee_profile_id)->first();
                $certificate_incharge = DigitalCertificate::with('digitalCertificateFile')->where('employee_profile_id', $signature_request->employee_head_profile_id)->first();

                if (!$certificate_owner || !$certificate_incharge) {
                    $results[] = ['id' => $request_data['id'], 'status' => 'error', 'message' => 'Digital certificate not found'];
                    continue;
                }

                $file_path = $signature_request->digitalDtrSignatureRequestFile->file_path;
                if (!Storage::disk('private')->exists($file_path)) {
                    $results[] = ['id' => $request_data['id'], 'status' => 'error', 'message' => 'File not found'];
                    continue;
                }

                $file_content = Storage::disk('private')->get($file_path);
                $mime_type = Storage::disk('private')->mimeType($file_path);
                $filename = basename($file_path);

                $temp_path = tempnam(sys_get_temp_dir(), 'uploaded_file_');
                file_put_contents($temp_path, $file_content);

                $uploaded_file = new \Illuminate\Http\UploadedFile(
                    $temp_path,
                    $filename,
                    $mime_type,
                    null,
                    true
                );

                $this->dtrSigningService->processOwnerSigning($uploaded_file, $certificate_owner, $request_data['whole_month']);
                $this->dtrSigningService->processInchargeSigning([$signature_request->id], $certificate_incharge, $request_data['whole_month']);

                $notification = Notifications::create([
                    'title' => $employee_name . ' has approved and signed your DTR',
                    'description' => 'Approval of Digital DTR Signature Request',
                    'module_path' => 'approve-dtr',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $certificate_owner->employee_profile_id,
                ]);

                Helpers::sendNotification([
                    'id' => $certificate_owner->employee_profile_id,
                    'data' => new NotificationResource($user_notification),
                ]);

                $results[] = ['id' => $request_data['id'], 'status' => 'success', 'message' => 'Signature request processed successfully'];
            }

            DB::commit();

            return response()->json([
                'message' => 'Multiple signature requests processed',
                'results' => $results
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'approveMultipleSignatureRequests', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
