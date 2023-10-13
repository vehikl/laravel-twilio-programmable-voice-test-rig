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
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\PhoneNumber;

// ...

/** @test */
public function itDoesABasicFlow(): void
{
    (new ProgrammableVoiceRig($this->app))
        ->from(new PhoneNumber('15554443322'))
        ->to(new PhoneNumber('12223334455'))
        ->endpoint(route('your.initial.twiml.route.here'))
        ->assertNextEndpoint(route('your.second.twiml.route.here')) // Assert that the initial endpoint routes to this endpoint
        ->assertTwiml('<Play>%s</Play><Hangup/>', Storage::url('your-file.mp3'))
        ->assertCallEnded();
}

/** @test */
public function itHandlesAVariableFlow(): void
{
    $routeTo = route('special.second.route');
    yourMethodToSetFlow($routeTo)
    (new ProgrammableVoiceRig($this->app))
        ->from(new PhoneNumber('15554443322'))
        ->to(new PhoneNumber('12223334455'))
        ->endpoint(route('initial.route.with.variable.redirect'))
        ->assertTwiml('<Redirect method="POST">%s</Redirect>', $routeTo)
        ->assertNextEndpoint($routeTo)
        ->assertTwiml('<Say>%s</Say><Hangup/>', 'The secret word of the day is apple')
        ->assertCallEnded();
}

/** @test */
public function itRecordsWithFallthrough(): void
{
    (new ProgrammableVoiceRig($this->app))
        ->from(new PhoneNumber('15554443322'))
        ->to(new PhoneNumber('12223334455'))

        ->queueInput(recordingUrl: 'file.mp3', recordingDuration: 5, digits: '123') // Removing this will cause the test to fail and the flow to go through missed.recording

        ->endpoint(route('start.recording'))
        ->assertTwiml('<Record action="%s"/><Redirect method="POST">%s</Redirect>', route('handle.recording'), route('missed.recording'))
        ->assertNextEndpoint(route('handle.recording'))
        ->assertTwiml('<Play>%s</Play><Hangup/>', 'Thanks for your recording')
        ->assertCallEnded();

    $this->assertDatabaseHas('my_twilio_recordings', [
        'audio' => 'file.mp3',
        'duration' => 5,
    ]);
}
```

## Setting up your tests

### Voice Calls

### SMS Messaging


## API

### Assertions

- [x] `assertRedirectedTo(uri, method)`
- [x] `assertCallEnded()`
- [x] `assertCallStatus(status)`
- [ ] `assertValidTwiml()`
- [x] `assertTwimlEquals(twml, ...replacements)` (works like sprintf)
- [x] `assertTwimlContains(twml, ...replacements)` (works like sprintf)
- [ ] `assertTwimlOrder([tag1Name, tag2Name])`
- [x] `assertSaid(textFromASayTag)`
- [ ] `assertPlayed(file)`
- [ ] `assertDialed(phoneNumber)`
- [ ] `assertPaused(numberOfSeconds)`
- [ ] `assertRejected(reason)`
- [ ] `assertStreamed(websocketUrl)`
- [ ] `assertRecorded(action, method = 'POST')`
- [ ] `assertGathered(action, method = 'POST')`

## Contributors

- Alex
- Ahmed
- Brad
- Hunter
- Ian
- Jeff C
- Justin S
