<?php

namespace App\Http\Controllers;

use App\Models\DigitalSignedDtr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Helpers;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DigitalSignedDtrController extends Controller
{
    private string $CONTROLLER_NAME = 'DigitalSignedDtrController';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = DigitalSignedDtr::query();

            // Filter by employee_profile_id if provided
            if ($request->has('employee_profile_id')) {
                $query->where('employee_profile_id', $request->input('employee_profile_id'));
            }

            // Filter by signer_type if provided
            if ($request->has('signer_type')) {
                $query->where('signer_type', $request->input('signer_type'));
            }

            // Filter by month_year if provided (YYYY-MM format)
            if ($request->has('month_year')) {
                $query->where('month_year', $request->input('month_year'));
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $documents = $query->orderBy('created_at', 'desc')->paginate(10);

            return response()->json($documents);
        } catch (\Throwable $th) {
            Log::error('Error in index: ' . $th->getMessage());
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $document = DigitalSignedDtr::with(['employeeProfile', 'digitalCertificate'])
                ->findOrFail($id);

            // Check if file exists in storage
            if (!Storage::disk('private')->exists($document->file_path)) {
                throw new \Exception('Document file not found in storage.');
            }

            if ($request->query('download', false)) {
                return Storage::disk('private')->download(
                    $document->file_path,
                    $document->file_name,
                    ['Content-Type' => 'application/pdf']
                );
            }

            return response()->json($document);
        } catch (\Throwable $th) {
            Log::error('Error in show: ' . $th->getMessage());
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:signed,archived,deleted'
            ]);

            $document = DigitalSignedDtr::findOrFail($id);
            $document->status = $request->input('status');
            $document->save();

            return response()->json([
                'message' => 'Document status updated successfully',
                'document' => $document
            ]);
        } catch (\Throwable $th) {
            Log::error('Error in update: ' . $th->getMessage());
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $document = DigitalSignedDtr::findOrFail($id);

            // Soft delete the record
            $document->delete();

            return response()->json([
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error in destroy: ' . $th->getMessage());
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get document file content
     */
    public function getFile($id)
    {
        try {
            $document = DigitalSignedDtr::findOrFail($id);

            if (!Storage::disk('private')->exists($document->file_path)) {
                throw new \Exception('Document file not found in storage.');
            }

            return Storage::disk('private')->response($document->file_path, $document->file_name, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error in getFile: ' . $th->getMessage());
            Helpers::errorLog($this->CONTROLLER_NAME, 'getFile', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
