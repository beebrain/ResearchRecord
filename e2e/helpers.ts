export const appBase =
  process.env.PLAYWRIGHT_BASE_URL ??
  'http://127.0.0.1/ResearchRecord/public/index.php';

/** CI4 query-string routes for nginx without mod_rewrite */
export function appRoute(path: string): string {
  const qIndex = path.indexOf('?');
  const segment = (qIndex >= 0 ? path.slice(0, qIndex) : path).replace(/^\//, '');
  const qs = qIndex >= 0 ? path.slice(qIndex) : '';

  const base = appBase.endsWith('?/') ? appBase : `${appBase}?/`;

  return `${base}${segment}${qs}`;
}
