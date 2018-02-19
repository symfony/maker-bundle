1.1
===

* [BC BREAK] The MakerInterface changed: `getParameters()`, `getFiles()`
  and `writeNextStepsMessage()` were removed and `generate()` was added
  in their place. We recommend extending `AbstractMaker` instead of implementing
  the interface directly, and use `$this->writeSuccessMessage()` to get
  the normal "success" message after the command.
