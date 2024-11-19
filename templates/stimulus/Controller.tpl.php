import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
<?= $targets ? "    static targets = $targets\n" : "" ?>
<?php if ($values) { ?>
    static values = {
<?php foreach ($values as $value): ?>
        <?= $value['name'] ?>: <?= $value['type'] ?>,
<?php endforeach; ?>
    }
<?php } ?>
    // ...
}
