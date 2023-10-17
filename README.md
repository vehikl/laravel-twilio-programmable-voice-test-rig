# Laravel Twilio Programmable Voice Test Rig

Test your programmable voice twiml call flows, start to finish.
Behind the scenes, uses PHPUnit static assertions, and not tested with Pest.

## You need this library if you:

 - :telephone: Use twilio's programmable voice for handling phone calls
 - :mailbox: Have multiple endpoints to handle the flow of a call
 - :twisted_rightwards_arrows: Multiple paths for the flow of a call

## Quick-Start

```php
<?php

// ...

use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

// ...

/** @test */
public function itDoesABasicFlow(): void
{
    // ...
}
```

## Setting up your tests

### Voice Calls

### SMS Messaging


## API

### Assertions

- [x] `assertTwimlEquals(twml, ...replacements)` (works like sprintf)
- [x] `assertTwimlContains(twml, ...replacements)` (works like sprintf)
- [x] `assertTwimlOrder([tag1Name, tag2Name])`
- [x] `assertRedirect(uri, method)`
- [x] `assertSay(textFromASayTag)`
- [x] `assertPlay(file)`
- [ ] `assertDial(phoneNumber)`
- [x] `assertPause(numberOfSeconds)`
- [ ] `assertStream(websocketUrl)`
- [ ] `assertRecord(attributes)`
- [ ] `assertGather(attributes, children?)`
- [x] `assertCallStatus(status)`
- [x] `assertTwilioHit(uri, method = 'POST', byTwimlTag = null)`
- [x] `assertCallEnded()`
- [ ] `assertRejected(reason)`

## Contributors

- Alex
- Ahmed
- Brad
- Hunter
- Ian
- John I
- John M
- Jeff C
- Justin S
