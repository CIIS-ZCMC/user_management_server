<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;

class FileService
{
    /**
     * Upload images on given folder
     * 
     * @param string $image_data
     * @param string $folder_name
     * @param string $prefix
     * @param string $suffix
     * 
     * @return bool
     * 
     */
    public function uploadImage($image_data, $folder_name, $prefix = null, $suffix = null)
    {
        try {
            $filename = ($prefix ? $prefix.'-' : '') . time() . ($suffix ? '-' . $suffix : ''). '.' .$image_data->getClientOriginalExtension();
            $path = $image_data->storeAs('public/'.$folder_name, $filename);
            return $filename;
        } catch (Exception $ex) {
            // dd($ex);
            return false;
        }
    }

    public function uploadRequirement($leave_application_requirement_id,$image_data, $folder_name, $prefix = null, $suffix = null)
    {
        try {
            $filename = ($prefix ? $prefix.'-' : '') . $leave_application_requirement_id .time() . ($suffix ? '-' . $suffix : ''). '.' .$image_data->getClientOriginalExtension();
            $path = $image_data->storeAs('public/'.$folder_name, $filename);
            return $filename;
        } catch (Exception $ex) {
            // dd($ex);
            return false;
        }
    }

    /**
     * Upload images on given folder
     * 
     * @param string $image
     * @param string $folder_name
     * 
     * @return bool
     */
    public function deleteImage($image, $folder_name)
    {
        try { 
            Storage::delete('public/'.$folder_name.'/'.$image); 
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
}
