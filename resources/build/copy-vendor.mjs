/**
 * Copies pinned third-party browser bundles out of node_modules and into
 * public/assets/vendor so the app serves them from its own origin.
 *
 * Why not a CDN: unpkg/@latest re-resolves on every page load, which means an
 * unreviewed upstream release can ship straight to users, and the extra DNS +
 * TLS handshake delays first paint. Vendoring pins the version and keeps the
 * app working offline.
 */
import { copyFile, mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const outDir = resolve(root, 'public/assets/vendor');

const assets = [
  { from: 'node_modules/lucide/dist/umd/lucide.min.js', to: 'lucide.min.js' },
];

await mkdir(outDir, { recursive: true });

for (const asset of assets) {
  await copyFile(resolve(root, asset.from), resolve(outDir, asset.to));
  console.log(`vendored  ${asset.to}`);
}

// Record the exact versions that were vendored so a stale bundle is obvious.
const pkg = JSON.parse(await readFile(resolve(root, 'package.json'), 'utf8'));
const manifest = {
  generatedAt: new Date().toISOString(),
  packages: { lucide: pkg.devDependencies.lucide },
};
await writeFile(resolve(outDir, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
console.log('vendored  manifest.json');
