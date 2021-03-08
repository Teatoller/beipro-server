<?php

namespace App\Http\Controllers;

use App\Models\BeiStaff;
use Illuminate\Http\Request;

class BeiStaffController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $beiStaffs = BeiStaff::all();
        return response($beiStaffs, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validateRequest();

        BeiStaff::create($data);

        return response()->json([
            "message" => "Beipro staff created succesfully",
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (BeiStaff::where('id', $id)->exists()) {
            $beistaff = BeiStaff::where('id', $id)->get();
            return response($beistaff, 200);
        } else {
            return response()->json([
                "message" => "Beipro staff not found",
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(BeiStaff $beistaff)
    {
        $data = $this->validateRequest();

        if ($beistaff->id) {
            $firstname = $beistaff->firstname;
            $lastname = $beistaff->lastname;
            $staff_id = $beistaff->staff_id;
            $email = $beistaff->email;
            $role = $beistaff->role;
            $location = $beistaff->location;

            $beistaff = BeiStaff::find($beistaff->id);
            $beistaff->firstname = is_null($firstname) ? $beistaff->firstname : $firstname;
            $beistaff->lastname = is_null($lastname) ? $beistaff->lastname : $lastname;
            $beistaff->staff_id = is_null($staff_id) ? $beistaff->staff_id : $staff_id;
            $beistaff->email = is_null($email) ? $beistaff->email : $email;
            $beistaff->role = is_null($role) ? $beistaff->role : $role;
            $beistaff->location = is_null($location) ? $beistaff->location : $location;

            $beistaff->update($data);

            return response()->json([
                "message" => "records updated successfully",
            ], 200);
        } else {
            return response()->json([
                "message" => "Beipro staff not found",
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(BeiStaff $beiStaff, $id)
    {
        if (BeiStaff::where('id', $id)->exists()) {
            $beiStaff = BeiStaff::find($id);
            $beiStaff->delete();
            return response()->json([
                "message" => "Beipro staff deleted",
            ], 202);
        } else {
            return response()->json([
                "message" => "Beipro staff not found",
            ], 404);
        }
    }

    public function validateRequest()
    {
        return request()->validate([
            'firstname' => 'required|alpha|string',
            'lastname' => 'required|alpha|string',
            'staff_id' => 'required|string',
            'email' => 'required|string|email',
            'role' => 'required|string|max:8',
            'location' => 'required|string',
        ]);
    }
}
