import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import vm from 'node:vm';

const helperPath = fileURLToPath(
    new URL('../../public/js/guru-matching-preview.js', import.meta.url)
);
const helperSource = readFileSync(helperPath, 'utf8');
const rendererPath = fileURLToPath(
    new URL('../../public/js/guru-kuis.js', import.meta.url)
);
const rendererSource = readFileSync(rendererPath, 'utf8');
const studentRendererPath = fileURLToPath(
    new URL('../../public/js/siswa.js', import.meta.url)
);
const studentRendererSource = readFileSync(studentRendererPath, 'utf8');
const guruCss = readFileSync(fileURLToPath(
    new URL('../../public/css/guru-kuis.css', import.meta.url)
), 'utf8');
const studentCss = readFileSync(fileURLToPath(
    new URL('../../public/css/siswa-materi.css', import.meta.url)
), 'utf8');

function createPreviewState(targets, leftItems, pairs, random) {
    const window = {};
    vm.runInNewContext(helperSource, { window });
    return window.SaluangMatchingPreview.createState(
        targets,
        leftItems,
        pairs,
        random
    );
}

function isFullyAligned(leftItems, rightItems, pairs) {
    const rightPositions = new Map(
        rightItems.map((item, index) => [String(item.id), index])
    );
    return leftItems.every((item, index) =>
        pairs.some(pair =>
            String(pair.left_id) === String(item.id)
            && rightPositions.get(String(pair.right_id)) === index
        )
    );
}

test('Guru matching preview keeps answers empty and breaks a fully aligned order', () => {
    const leftItems = ['left-a', 'left-b', 'left-c', 'left-d']
        .map(id => ({ id }));
    const targets = ['right-a', 'right-b', 'right-c', 'right-d']
        .map(id => ({ id }));
    const pairs = leftItems.map((item, index) => ({
        left_id: item.id,
        right_id: targets[index].id
    }));

    const preview = createPreviewState(
        targets,
        leftItems,
        pairs,
        () => 0.999999
    );

    assert.deepEqual([...preview.answers], []);
    assert.equal(preview.selectedLeftId, null);
    assert.deepEqual(
        [...preview.rightOrder].map(item => item.id).sort(),
        targets.map(item => item.id).sort()
    );
    assert.equal(isFullyAligned(leftItems, preview.rightOrder, pairs), false);
});

test('Guru matching preview detects alignment when a left item has multiple correct targets', () => {
    const leftItems = ['left-a', 'left-b', 'left-c'].map(id => ({ id }));
    const targets = ['right-a', 'right-b', 'right-c'].map(id => ({ id }));
    const pairs = [
        { left_id: 'left-a', right_id: 'right-a' },
        { left_id: 'left-a', right_id: 'right-b' },
        { left_id: 'left-b', right_id: 'right-b' },
        { left_id: 'left-c', right_id: 'right-c' }
    ];

    const preview = createPreviewState(
        targets,
        leftItems,
        pairs,
        () => 0.999999
    );

    assert.equal(isFullyAligned(leftItems, preview.rightOrder, pairs), false);
    assert.deepEqual([...preview.answers], []);
});

test('Guru question detail renderer shows shuffled columns without answer lines', () => {
    const start = rendererSource.indexOf(
        '    function buildActivityBoardMarkup(question) {'
    );
    const end = rendererSource.indexOf(
        '    const bindActivityImageFallback',
        start
    );
    assert.notEqual(start, -1);
    assert.notEqual(end, -1);

    const context = {
        window: {},
        Math: { floor: Math.floor, random: () => 0.999999 },
        normalizeMatchingPairs: pairs => Array.isArray(pairs)
            ? pairs.filter(pair => pair && pair.left_id != null && pair.right_id != null)
            : Object.entries(pairs || {}).flatMap(([leftId, rightIds]) =>
                (Array.isArray(rightIds) ? rightIds : [rightIds])
                    .filter(rightId => rightId != null)
                    .map(rightId => ({ left_id: leftId, right_id: rightId }))
            ),
        activityCardMarkup: item => `<card>${item.label}</card>`,
        escapeHtml: value => String(value)
    };
    vm.runInNewContext(helperSource, context);
    vm.runInNewContext(
        `${rendererSource.slice(start, end)}\nthis.buildActivityBoardMarkup = buildActivityBoardMarkup;`,
        context
    );

    const leftItems = ['left-a', 'left-b', 'left-c']
        .map((id, index) => ({ id, label: `Kiri ${index + 1}` }));
    const rightItems = ['right-a', 'right-b', 'right-c']
        .map((id, index) => ({ id, label: `Kanan ${index + 1}` }));
    const markup = context.buildActivityBoardMarkup({
        activity_type: 'matching',
        activity_payload: {
            left_items: leftItems,
            right_items: rightItems,
            pairs: leftItems.map((item, index) => ({
                left_id: item.id,
                right_id: rightItems[index].id
            }))
        }
    }).markup;

    assert.match(markup, /Bagian Kiri/);
    assert.match(markup, /Bagian Kanan/);
    assert.match(markup, /gq-activity-detail-lines/);
    assert.match(markup, /data-matching-pairs=/);
    assert.doesNotMatch(markup, /<line\b/);
    assert.deepEqual(
        [...markup.matchAll(/<card>(Kanan \d)<\/card>/g)].map(match => match[1]).sort(),
        ['Kanan 1', 'Kanan 2', 'Kanan 3']
    );
    const displayedRightOrder = [...markup.matchAll(/<card>(Kanan \d)<\/card>/g)]
        .map(match => match[1]);
    assert.notDeepEqual(displayedRightOrder, ['Kanan 1', 'Kanan 2', 'Kanan 3']);
});

test('Guru matching lines follow pair IDs and include every one-to-many connection', () => {
    const positions = {
        'left-a': { left: 120, top: 220, width: 10, height: 10 },
        'left-b': { left: 120, top: 320, width: 10, height: 10 },
        'right-a': { left: 570, top: 320, width: 10, height: 10 },
        'right-b': { left: 570, top: 220, width: 10, height: 10 }
    };
    const rendered = [];
    const document = {
        createElementNS: () => ({
            attributes: {},
            classList: { add() {} },
            setAttribute(name, value) {
                this.attributes[name] = value;
            }
        })
    };
    const window = { document };
    const context = {
        window,
        CSS: { escape: value => value }
    };
    vm.runInNewContext(helperSource, context);

    const board = {
        getBoundingClientRect: () => ({ left: 100, top: 200, width: 500, height: 300 }),
        querySelector(selector) {
            const [, side, id] = selector.match(/data-(match-left|match-right)="([^"]+)"/) || [];
            const itemId = id;
            const prefix = side === 'match-left' ? 'left-' : 'right-';
            const position = positions[itemId.startsWith(prefix) ? itemId : `${prefix}${itemId}`];
            return position ? { getBoundingClientRect: () => position } : null;
        }
    };
    const svg = {
        setAttribute() {},
        replaceChildren() {
            rendered.length = 0;
        },
        appendChild(line) {
            rendered.push(line);
        }
    };
    const pairs = [
        { left_id: 'a', right_id: 'b' },
        { left_id: 'a', right_id: 'a' },
        { left_id: 'b', right_id: 'b' }
    ];

    window.SaluangMatchingPreview.drawLines(
        board,
        svg,
        pairs,
        'match-left',
        'match-right',
        'gq-activity-detail-dot',
        'gq-activity-detail-line'
    );

    assert.equal(rendered.length, 3);
    assert.deepEqual(
        rendered.map(line => [
            line.attributes.x1,
            line.attributes.y1,
            line.attributes.x2,
            line.attributes.y2
        ]),
        [
            ['25', '25', '475', '25'],
            ['25', '25', '475', '125'],
            ['25', '125', '475', '25']
        ]
    );
});

test('activity cards omit empty image frames and keep failed-image fallback compact', () => {
    const start = rendererSource.indexOf('    const activityImageMarkup =');
    const end = rendererSource.indexOf('    const questionTypeLabel', start);
    const context = {
        escapeHtml: value => String(value)
    };
    vm.runInNewContext(
        `${rendererSource.slice(start, end)}\nthis.activityCardMarkup = activityCardMarkup;`,
        context
    );

    const textCard = context.activityCardMarkup({ label: 'Predasi' }, 'Pasangan');
    const imageCard = context.activityCardMarkup({
        label: 'Mutualisme',
        image_url: '/images/mutualisme.jpg'
    }, 'Pasangan');
    assert.doesNotMatch(textCard, /image-frame|Gambar tidak tersedia/);
    assert.match(textCard, /is-text-only/);
    assert.match(imageCard, /image-frame/);
    assert.match(imageCard, /Gambar tidak tersedia/);

    const studentStart = studentRendererSource.indexOf('    function addActivityImage(parent, item) {');
    const studentEnd = studentRendererSource.indexOf('    function updateActivityAnswer', studentStart);
    const children = [];
    const classNames = [];
    const frame = {
        appendChild(child) {
            children.push(child);
        },
        replaceChildren(child) {
            children.splice(0, children.length, child);
        }
    };
    const studentContext = {
        el(tag, className, text) {
            if (tag === 'span' && className === 'sd-activity-image-frame') return frame;
            return { tag, className, text };
        }
    };
    vm.runInNewContext(
        `${studentRendererSource.slice(studentStart, studentEnd)}\nthis.addActivityImage = addActivityImage;`,
        studentContext
    );
    const textParent = {
        classList: { add(className) { classNames.push(className); } },
        appendChild(child) { children.push(child); }
    };
    studentContext.addActivityImage(textParent, { label: 'Mutualisme' });
    assert.deepEqual(classNames, ['is-text-only']);
    assert.equal(children.length, 0);

    assert.match(guruCss, /object-fit:\s*cover;\s*object-position:\s*center;/);
    assert.match(studentCss, /object-fit:\s*cover;\s*object-position:\s*center;/);
    assert.match(studentCss, /\.sd-page #lessonPage \.sd-matching-card\.is-text-only/);
});
