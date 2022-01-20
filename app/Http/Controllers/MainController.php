<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MainController extends Controller
{

    const STUDENT = "Student";
    const TEACHER = "Teacher";
    const EMPLOYEE = "Employee";

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function runAttendance()
    {
        $devices = DB::table('devices')->where('status', 1)->get();

        foreach ($devices as $device){

            if ($device->type == self::STUDENT){
                (new StudentAttendanceController($device->ip))->run();
            }

            if ($device->type == self::TEACHER){
                (new TeacherAttendanceController($device->ip))->run();
            }

            if ($device->type == self::EMPLOYEE){
                (new EmployeeAttendanceController($device->ip))->run();
            }


        }

        return true;
    }


}
