/**
 * Node test: normalizeAIResponsePayload logic (no browser).
 * Run: node test-ai-response-node.js
 */
function normalizeAIResponsePayload(result) {
    if (!result) return null;
    if (Array.isArray(result) && result.length > 0) {
        return result[0];
    }
    if (result.output !== undefined) {
        var out = result.output;
        return Array.isArray(out) && out.length > 0 ? out[0] : out;
    }
    if (typeof result === 'object' && (result.type || result.title_en || result.title_th || result.journalname || result.source === 'ai_extraction')) {
        return result;
    }
    return null;
}

var n8nResponse = [
    {
        source: 'ai_extraction',
        type: 'journal',
        journalname: 'Journal of Nursing and Health Science Research',
        title_en: 'The Model Efficiency Management of the Rehabilitation and Nursing Home Center Uttaradit Rajabhat University',
        title_th: 'รูปแบบการบริหารจัดการที่มีประสิทธิภาพของศูนย์เวชศาสตร์ฟื้นฟูและดูแลผู้สูงอายุ มหาวิทยาลัยราชภัฏอุตรดิตถ์',
        authors_en: ['Kanyarat Phuengbanhan', 'Anurak Panyanuwat', 'Nicharee Jaikamwang', 'Chichaya Changrian', 'Pimradar Tummeepukdee'],
        authors_th: ['กัญญารัตน์ ผึ่งบรรหาร', 'อนุรักษ์ ปัญญานุวัฒน์', 'ณิชารีย์ ใจค าวัง', 'ชิชญาสุ์ ช่างเรียน', 'พิมพ์รดา ธรรมีภักดี'],
        volume: 16,
        issue: 2,
        year_en: '2024',
        year_th: 2567
    }
];

var tests = [
    {
        name: 'n8n root-level array',
        input: n8nResponse,
        check: function (p) {
            return p && p.title_th && p.journalname === 'Journal of Nursing and Health Science Research' && p.authors_th.length === 5;
        }
    },
    {
        name: '{ output: array }',
        input: { output: n8nResponse },
        check: function (p) { return p && p.title_th === n8nResponse[0].title_th; }
    },
    {
        name: '{ output: single object }',
        input: { output: n8nResponse[0] },
        check: function (p) { return p && p.journalname === n8nResponse[0].journalname; }
    },
    {
        name: 'empty array -> null',
        input: [],
        check: function (p) { return p === null; }
    },
    {
        name: 'null -> null',
        input: null,
        check: function (p) { return p === null; }
    },
    {
        name: 'direct object (single extraction at root)',
        input: { source: 'ai_extraction', type: 'journal', journalname: 'Test Journal', title_th: 'ชื่อไทย' },
        check: function (p) { return p && p.source === 'ai_extraction' && p.title_th === 'ชื่อไทย'; }
    }
];

var pass = 0, fail = 0;
tests.forEach(function (t) {
    var out = normalizeAIResponsePayload(t.input);
    var ok = t.check(out);
    if (ok) { pass++; console.log('PASS: ' + t.name); }
    else { fail++; console.log('FAIL: ' + t.name); }
});

console.log('\nTotal: ' + pass + ' passed, ' + fail + ' failed');
process.exit(fail > 0 ? 1 : 0);
