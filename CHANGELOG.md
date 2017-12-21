1.1
===

* [BC BREAK] The MakerInterface changed: `writeNextStepsMessage`
  was renamed to `writeSuccessMessage`. You should now extend
  `AbstractMaker` instead of implementing the interface directly,
  and use `parent::writeSuccessMessage()` to get the normal success
  message after the command.
