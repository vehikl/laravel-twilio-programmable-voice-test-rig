<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

enum Rejection: string
{
    case busy = 'busy';
    case rejected = 'rejected';
}
