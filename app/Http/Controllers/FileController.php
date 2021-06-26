<?php

namespace App\Http\Controllers;

use App\Models\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(File $file)
    {
        return $file->all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $file_size = filesize($request->file('file'));
        $file_name = $request->file('file')->getClientOriginalName();
        try{
            $file_location = Storage::putFileAs('files', $request->file('file'), $file_name);
        } catch(Exception $e){
            return response()->json(['Error Storing File.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        $data['file_name'] = $file_name;
        $data['file_size'] = $file_size;
        $data['file_location'] = $file_location;
        $saved_file = File::create($data);

        if(!$saved_file){
            Storage::delete([$file_name]);
            return response()->json(['File Not Saved'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json($saved_file, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return File::findOrFail($id);
    }

    /**
     * Downloads the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function download($id)
    {
        $file = File::findOrFail($id);

        return Storage::download($file->file_location);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $file = File::findOrFail($id);
        
        $request->validate([
            'file' => 'required|file'
        ]);
        
        if(Storage::exists($file->file_location)){
            Storage::delete([$file->file_location]);
        }

        $file_name = $request->file('file')->getClientOriginalName();
        $file_size = filesize($request->file('file'));
        try{
            $file_location = Storage::putFileAs('files', $request->file('file'), $file_name);
        } catch(Exception $e) {
            return response()->json(['Error Storing Updated File.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $file->file_name = $file_name;
        $file->file_size = $file_size;
        $file->file_location = $file_location;
        $updated_file = $file->update();

        if(!$updated_file){
            Storage::delete([$file_name]);
            return response()->json(['File Not Updated'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json($updated_file, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $file = File::findOrFail($id);

        try{
            Storage::delete([$file->file_location]);
        } catch(Exception $e) {
            return response()->json(['Error Deleting File. File Not Deleted.'], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

        $file_deleted = $file->delete();

        if(!$file_deleted){
            return response()->json(['Error Deleting File. File Not Deleted.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['File Deleted.'], Response::HTTP_NO_CONTENT);
    }
}
