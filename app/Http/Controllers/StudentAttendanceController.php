<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\AttendanceLog;
use App\Services\ZKTService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentAttendanceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $ip;
    private $service;
    private $students = "students";
    private $studentAttendances = "student_attendances";

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

            $students = DB::table($this->students)->select('id', 'name', 'phone')->whereNull('device_id')->where('status', 1)->get();

           // dd($students);

            foreach ($students as $student){

                //Making device ID;
                $deviceId = ($student->id + 100);
                $pass = substr($student->phone, -5);

                $oldStudent = count($this->service->getUser());
                //$this->service->disableDevice();
                $this->service->setUser($deviceId, $deviceId, $student->name, $pass, 0, 0);

                $newStudent = count($this->service->getUser());

                if ($oldStudent < $newStudent){
                    DB::table($this->students)->where('id', $student->id)->update(['device_id' => $deviceId, 'modified_at' => Carbon::now()]);
                }

                //dd($this->service->clearUsers());
                //dd($this->service->getAttendance());

            }
            //var_dump($this->service->clearUsers());
            //dd($this->service->clearAttendance());
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
                    ->where('student_id', $att['id'])
                    ->where('punch_time', 'LIKE', '%'.Carbon::parse($att['timestamp'])->format('Y-m-d').'%')
                    ->count();

                if (! $exist) {

                    DB::table('attendance_log')->insert([
                        'student_id' => $att['id'],
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
    private function createAttendance(){

        $logs = AttendanceLog::where('status', 0)->whereNotNull('student_id')->get();

        DB::beginTransaction();

        try {

            foreach ($logs as $log)
            {

                $student = DB::table($this->students)->join('enrollments', 'enrollments.student_id', $this->students . '.id')
                    ->where($this->students . '.device_id', $log->student_id)
                    ->select('enrollments.*')
                    ->first();

                if (! $student){
                    continue;
                }


                $exist = DB::table($this->studentAttendances)
                    ->where('school_id', $student->school_id)
                    ->where('student_id', $student->student_id)
                    ->where('month', Carbon::parse($log->punch_time)->format('m'))
                    ->where('year', Carbon::parse($log->punch_time)->format('Y'))
                    ->count();


                if ($exist)
                {

                    DB::table($this->studentAttendances)
                        ->where('student_id', $student->student_id)
                        ->where('school_id', $student->school_id)
                        ->where('month', Carbon::parse($log->punch_time)->format('m'))
                        ->where('year', Carbon::parse($log->punch_time)->format('Y'))
                        ->update([
                            'day_' . Carbon::parse($log->punch_time)->format('d') => "P",
                            'modified_at' => Carbon::now(),
                        ]);
                }
                else
                {
                    DB::table($this->studentAttendances)->insert([
                            'school_id' => $student->school_id,
                            'student_id' => $student->student_id,
                            'class_id' => $student->class_id,
                            'section_id' => $student->section_id,
                            'academic_year_id' => $student->academic_year_id,
                            'month' => Carbon::parse($log->punch_time)->format('m'),
                            'year' => Carbon::parse($log->punch_time)->format('Y'),
                            'day_' . Carbon::parse($log->punch_time)->format('d') => "P",
                            'status' => 1,
                            'created_at' => $log->punch_time,
                        ]);
                }

                AttendanceLog::where('id', $log->id)->update(['status' => 1]);

            }



            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            dd($exception);

            Log::error('Attendance create Error');
            Log::info($exception->getMessage());

        }

        return $this;

    }


}
