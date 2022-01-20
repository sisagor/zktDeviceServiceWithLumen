<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Rats\Zkteco\Lib\ZKTeco;

class ZKTService
{

    protected $zkt;

    public function __construct($ip)
    {
        //Create Object
        $this->zkt = new ZKTeco($ip);
        //Check connection
    }

    #connect Device:
    public function connect()
    {
        $connection = $this->zkt->connect();

        if (! $connection){

            Log::error("Device not connected");
            return false;
        }

        return true;
    }

    #disconnect Device:
    public function disconnect()
    {
        return $this->zkt->disconnect();
    }

    #Enable Device:
    public function enableDevice()
    {
        return $this->zkt->enableDevice();
    }

    #Disable Device:
    public function disableDevice()
    {
        return $this->zkt->disableDevice();
    }

    #Device Version:
    public function version()
    {
        return $this->zkt->version();
    }

    #device Name:
    public function deviceName()
    {
        return $this->zkt->deviceName();
    }


    /*Get user*/
    public function getUser()
    {
        $this->zkt->enableDevice();
        return $this->zkt->getUser();

    }

    #set user
    public function setUser($uid, $userId, $name, $password, $role = 0, $cardNo = 0)
    {
        $this->zkt->enableDevice();
        $user =  $this->zkt->setUser($uid, $userId, $name, $password, $role, $cardNo);
        $this->zkt->disableDevice();

        return $user;
    }

    #remove user
    public function removeUser($uId)
    {
        return $this->zkt->removeUser($uId);
    }

    #get Attendance
    public function getAttendance()
    {
        $this->zkt->enableDevice();
        return $this->zkt->getAttendance();

    }

    #remove Attendance
    public function clearAttendance()
    {
        return $this->zkt->clearAttendance();
    }

    #Clear Users
    public function clearUsers()
    {
        $this->zkt->enableDevice();
        return $this->zkt->clearUsers();
    }

    #Clear Users
    public function deviceIp()
    {
        return $this->zkt->_ip;
    }

    //Restart device
    public function restart()
    {
        return $this->zkt->restart();
    }



}
