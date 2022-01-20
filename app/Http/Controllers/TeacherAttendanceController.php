<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Services\ZKTService;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherAttendanceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $ip;
    private $service;
    private $teachers = "teachers";
    private $teacherAttendances = "teacher_attendances";
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

            $teachers = DB::table($this->table)->select('id', 'name', 'phone')->whereNull('device_id')->where('status', 1)->get();

            foreach ($teachers as $teaccher){

                //Making device ID;
                $deviceId = sprintf("%'.0".env('DEVICE_ID_LENGTH', '5')."d", $teaccher->id);

                $this->service->setUser($deviceId, $deviceId, $teaccher->name, $teaccher->phone, 0, 0);

                $new = count($this->service->getUser());

                if ($old < $new){
                    DB::table($this->table)->where('id', $teaccher->id)->update(['device_id' => $deviceId, 'modified_at' => Carbon::now()]);
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

                $exist = AttendanceLog::where('teacher_id', $att['id'])
                    ->where('punch_time', 'LIKE', '%'.Carbon::parse($att['timestamp'])->format('Y-m-d').'%')
                    ->count();

                if (! $exist) {

                   AttendanceLog::create([
                        'teacher_id' => $att['id'],
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


    /*create Teacher attendances*/
    private function createAttendance():object
    {

        DB::beginTransaction();

        try {

            $logs = AttendanceLog::where('status', 0)->whereNotNull('teacher_id')->get();

            foreach ($logs as $log) {

                //Get teacher information
                $teacher = DB::table($this->teachers)
                    ->join($this->schools, $this->schools . '.id', $this->teachers . '.school_id')
                    ->select($this->schools . '.academic_year_id', $this->teachers . '.device_id', $this->teachers . '.id')
                    ->where($this->teachers . '.device_id', $log->teacher_id)
                    ->first();

                //Check if exist this month
                $exist = DB::table($this->teacherAttendances)
                    ->where('teacher_id', $log->teacher_id)
                    ->where('month', Carbon::parse($log->punch_time)->format('m'))
                    ->where('year', Carbon::parse($log->punch_time)->format('Y'))
                    ->count();


                if ($exist) {

                    //update attendance
                    DB::table($this->studentAttendances)
                        ->where('teacher_id', $log->student_id)
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
                    DB::table($this->studentAttendances)
                        ->where('teacher_id', $log->student_id)
                        ->where('month', Carbon::parse($log->punch_time)->format('m'))
                        ->where('year', Carbon::parse($log->punch_time)->format('Y'))
                        ->update([
                            'school_id ' => $teacher->school_id,
                            'teacher_id' => $teacher->teacher_id,
                            'academic_year_id' => $teacher->academic_year_id,
                            'month' => Carbon::parse($log->punch_time)->format('m'),
                            'year' => Carbon::parse($log->punch_time)->format('Y'),
                            'day_' . Carbon::parse($log->punch_time)->format('d') => "P",
                            'created_at' => $log->punch_time,
                        ]);
                }
            }
            DB::commit();
        }
        catch (\Exception $exception){
            DB::rollBack();
            dd($exception);

            Log::error('teacher attendance create Error');
            Log::info($exception->getMessage());

        }

        return $this;
    }




}
