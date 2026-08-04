/*
 * Executes a generated Stimulus controller for real:
 *   1. bundles the exact generated source with esbuild (so bare
 *      "@hotwired/stimulus" imports resolve against this pinned toolchain),
 *   2. imports the bundle and registers it in a real Stimulus Application
 *      inside jsdom,
 *   3. waits for the controller to connect to a matching DOM element,
 *   4. prints the controller's static API (targets/values/classes) as JSON
 *      for the PHP side to assert on.
 *
 * Usage: node run-stimulus.mjs <absolute-entry-file> <identifier>
 */

import { build } from 'esbuild';
import { JSDOM } from 'jsdom';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const [entry, identifier] = process.argv.slice(2);
if (!entry || !identifier) {
    console.error('usage: run-stimulus.mjs <entry-file> <identifier>');
    process.exit(2);
}

const toolchainDir = dirname(fileURLToPath(import.meta.url));

const result = await build({
    entryPoints: [entry],
    bundle: true,
    write: false,
    format: 'esm',
    nodePaths: [join(toolchainDir, 'node_modules')],
    logLevel: 'silent',
});

const dom = new JSDOM(`<!DOCTYPE html><html><body><div data-controller="${identifier}"></div></body></html>`);

globalThis.window = dom.window;
globalThis.document = dom.window.document;
for (const name of ['MutationObserver', 'Element', 'HTMLElement', 'Node', 'CustomEvent', 'Event', 'DOMParser', 'NodeList']) {
    globalThis[name] = dom.window[name];
}
globalThis.requestAnimationFrame ??= (cb) => setTimeout(cb, 0);

const { Application } = await import('@hotwired/stimulus');

const bundle = result.outputFiles[0].text;
const module = await import('data:text/javascript;base64,' + Buffer.from(bundle).toString('base64'));
const ControllerClass = module.default;

if (typeof ControllerClass !== 'function') {
    console.error('The generated controller has no default export.');
    process.exit(1);
}

const application = Application.start();
application.register(identifier, ControllerClass);

// stimulus connects asynchronously
await new Promise((resolve) => setTimeout(resolve, 100));

const element = dom.window.document.querySelector(`[data-controller="${identifier}"]`);
const controller = application.getControllerForElementAndIdentifier(element, identifier);

if (!controller) {
    console.error(`Controller "${identifier}" did not connect to the DOM.`);
    process.exit(1);
}

console.log(JSON.stringify({
    connected: true,
    targets: ControllerClass.targets ?? [],
    values: Object.keys(ControllerClass.values ?? {}),
    classes: ControllerClass.classes ?? [],
}));
