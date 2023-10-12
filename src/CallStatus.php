<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

enum CallStatus: string
{
    case busy = 'busy';
    case canceled = 'canceled';
    case completed = 'completed';
    case failed = 'failed';
    case in_progress = 'in-progress';
    case no_answer = 'no-answer';
    case queued = 'queued';
    case ringing = 'ringing';
}
