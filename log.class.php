<?php
namespace db;

class Log
{
    // @string, Log file path
    private $path = '/log/';

    // @void, Default constructor, sets the timezone and path of the log files.
    public function __construct()
    {
        date_default_timezone_set('Asia/Tehran');
        $this->path = dirname(__FILE__) . $this->path;
    }

    /*
     * Creates the log
     * @param string $message : the message which is written into the log
     *
     * 1. Checks if directory exist, if not, create one and call this method again
     * 2. Checks if log already exists
     * 3. If not, new log gets created. Log is written into the logs folder.
     * 4. Logname is current date(YEAR-MONTH-DAY)
     * 5. If log exists, edit method called
     * 6. Edit method modifies the current log
     * */
    public function write($message)
    {
        $date = new \DateTime();
        $log = $this->path . $date->format('Y-m-d') . ".txt";

        if (is_dir($this->path)) {
            if (!file_exists($log)) {
                $fh = fopen($log, 'a+') or die("Fatal Error!");
                $logContent = "Time: " . $date->format('H:i:s') . "\r\n" . $message . "\r\n";
                fwrite($fh, $logContent);
                fclose($fh);
            } else {
                $this->edit($log, $date, $message);
            }
        } else {
            if (mkdir($this->path, 0777) === true) {
                $this->write($message);
            }
        }
    }

    /*
     * Gets called if log exists
     * Modifies current log and adds the message to the log
     *
     * @param string $log
     * @param DateTimeObject $date
     * @param string $message
     */
    private function edit($log, $date, $message)
    {
        $logContent = "Time: " . $date->format('H:i:s') . "\r\n" . $message . "\r\n\r\n";
        $logContent .= file_get_contents($log);
        file_put_contents($log, $logContent);
    }
}