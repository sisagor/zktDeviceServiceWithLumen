<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    public $timestamps = false;

    protected $table = 'attendance_log';


    protected $fillable = [
        'id', 'student_id', 'teacher_id', 'employee_id', 'state', 'type', 'punch_time'
    ];

}
