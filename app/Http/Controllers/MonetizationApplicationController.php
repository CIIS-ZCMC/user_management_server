<?php

namespace App\Http\Controllers;

use App\Models\MonetizationApplication;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MonetizationApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{ 
            $mone_applications=[];
            
            $mone_applications =MonetizationApplication::with(['logs'])->get();
          
           
             return response()->json(['data' => $mone_applications], Response::HTTP_OK);
        }catch(\Throwable $th){
        
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application = new ObApplication();
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application->date_from = $request->date_from;
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application->date_to = $request->date_to;
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application->business_from = $request->business_from;
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application->business_to = $request->business_to;
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application->status = "for-approval-supervisor";
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application->reason = "for-approval-supervisor";
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application_id = $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application->id; 
                    foreach ($requirements as $requirement) {
                        $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement = $this->storeOfficialBusinessApplicationRequirement($public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_application_id);
                        $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement_id = $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement->id;

                        if($public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement_id = ObApplicationRequirement::where('id','=',$public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement->id)->first();  
                                if($public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement  ){
                                    $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement_name = $requirement->getobOriginalName();
                                    $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement =  ObApplicationRequirement::findOrFail($public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement->id);
                                    $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement->name = $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement_name;
                                    $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement->filename = $uploaded_image;
                                    $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $public function store(Request $request)
    {
        try{
            $official_business_application = new ObApplication();
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialBusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.ob') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getobOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Applied";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MonetizationApplication $monetizationApplication)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MonetizationApplication $monetizationApplication)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MonetizationApplication $monetizationApplication)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MonetizationApplication $monetizationApplication)
    {
        //
    }
}
