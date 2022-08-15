<?php

namespace Horizom\VarDumper;

class RCliTextFormatter extends RTextFormatter
{
    public function sectionTitle($title)
    {
        $pad = str_repeat(' ', $this->indent + 2);
        $this->out .= sprintf("\n\n%s\x1b[4;97m%s\x1b[0m", $pad, $title);
    }

    public function startExp()
    {
        $this->out .= "\x1b[1;44;96m ";
    }

    public function endExp()
    {
        if (VarDumper::config('showBacktrace') && ($trace = VarDumper::getBacktrace())) {
            $this->out .= "\x1b[0m\x1b[44;36m " . $trace['file'] . ':' . $trace['line'];
        }

        $this->out .=  " \x1b[0m\n";
    }

    public function endRoot()
    {
        $this->out .= "\n";
        if (($timeout = VarDumper::getTimeoutPoint()) > 0) {
            $this->out .= sprintf("\n\x1b[3;91m-- Listing incomplete. Timed-out after %4.2fs --\x1b[0m\n", $timeout);
        }
    }
}
