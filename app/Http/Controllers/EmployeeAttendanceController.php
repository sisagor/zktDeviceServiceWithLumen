<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\AttendanceLog;
use App\Services\ZKTService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeAttendanceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $ip;
    private $service;
    private $employee = "employees";
    private $employeeAttendance = "employee_attendances";
    private $schools = "schools";

    public function __construct($ip)
    {
        $this->ip = $ip;
        $this->service = new ZKTService($ip);

    }

    /*this function will run all needs*/
    public function run()
    {
       self::create();
       self::attendanceLog();
       self::createAttendance();

    }


    public function create(): object
    {
        if ($this->service->connect())
        {
            $old = count($this->service->getUser());

            $employees = DB::table($this->employee)->select('id', 'name', 'phone')->whereNull('device_id')->where('status', 1)->get();

            foreach ($employees as $employee){

                //Making device ID;
                $deviceId = sprintf("%'.0".env('DEVICE_ID_LENGTH', '5')."d", $employee->id);

                $this->service->setUser($deviceId, $deviceId, $employee->name, $employee->phone, 0, 0);

                $new = count($this->service->getUser());

                if ($old < $new){
                    DB::table($this->employee)->where('id', $employee->id)->update(['device_id' => $deviceId, 'modified_at' => Carbon::now()]);
                }
                //dd($this->service->getUser());
                //dd($this->service->getAttendance());
                //dd($this->service->removeUser($deviceId));
            }
        }
        else
        {
            Log::error('Device Error');
            Log::info('device not connected');
        }

        return $this;
    }


    public function attendanceLog() : object
    {
        if ($this->service->connect())
        {
            $attendances = $this->service->getAttendance();

            //dd($attendances);

            foreach ($attendances as $att){

                $exist = DB::table('attendance_log')
                    ->where('employee_id', $att['id'])
                    ->where('punch_time', 'LIKE', '%'.Carbon::parse($att['timestamp'])->format('Y-m-d').'%')
                    ->count();

                if (! $exist) {

                    DB::table('attendance_log')->insert([
                        'employee_id' => $att['id'],
                        'state' => $att['state'],
                        'type' => $att['type'],
                        'punch_time' => $att['timestamp'],
                    ]);

                }
            }
        }
        else
        {
            Log::error('Device Error');
            Log::info('device not connected');
        }

        return $this;
    }


    /*create attendance*/
    private function createAttendance(): object
    {

        $logs = AttendanceLog::where('status', 0)->whereNotNull('employee_id')->get();

        DB::beginTransaction();

        try
        {

            foreach ($logs as $log)
            {
                //Get employee information
                $employee = DB::table($this->employee)
                    ->join($this->schools, $this->schools . '.id', $this->employee . '.school_id')
                    ->select($this->schools . '.academic_year_id', $this->employee . '.device_id', $this->employee . '.id')
                    ->where($this->employee . '.device_id', $log->employee_id)
                    ->first();


                //Check if exist this month
                $exist = DB::table($this->employeeAttendance)
                    ->where('employee_id', $log->employee_id)
                    ->where('month', Carbon::parse($log->punch_time)->format('m'))
                    ->where('year', Carbon::parse($log->punch_time)->format('Y'))
                    ->count();


                if ($exist)
                {

                    //update attendance
                    DB::table($this->employeeAttendance)
                        ->where('employee_id', $log->employee_id)
                        ->where('month', Carbon::parse($log->punch_time)->format('m'))
                        ->where('year', Carbon::parse($log->punch_time)->format('Y'))
                        ->update([
                            'day_' . Carbon::parse($log->punch_time)->format('d') => "P",
                            'modified_at' => Carbon::now(),
                        ]);
                }
                else
                {
                    //create attendance
                    DB::table($this->employeeAttendance)
                        ->where('employee_id', $log->employee_id)
                        ->where('month', Carbon::parse($log->punch_time)->format('m'))
                        ->where('year', Carbon::parse($log->punch_time)->format('Y'))
                        ->update([
                            'school_id ' => $employee->school_id,
                            'employee_id' => $employee->employee_id,
                            'academic_year_id' => $employee->academic_year_id,
                            'month' => Carbon::parse($log->punch_time)->format('m'),
                            'year' => Carbon::parse($log->punch_time)->format('Y'),
                            'day_' . Carbon::parse($log->punch_time)->format('d') => "P",
                            'created_at' => $log->punch_time,
                        ]);
                }

            }


            DB::commit();

        }
        catch (\Exception $exception)
        {
            DB::rollBack();
            dd($exception);

            Log::error('Attendance create Error');
            Log::info($exception->getMessage());

        }

        return $this;
    }


}
