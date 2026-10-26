/* Student material rendering: safe content, media, activities, and summaries. */
(function () {
    'use strict';
    let el;
    const allowedMaterialTags = new Set([
        'P', 'BR', 'H2', 'H3',
        'STRONG', 'B', 'EM', 'I', 'U',
        'UL', 'OL', 'LI', 'DIV'
    ]);
    const blockedMaterialTags = new Set([
        'SCRIPT', 'STYLE', 'IFRAME',
        'OBJECT', 'EMBED', 'SVG', 'MATH',
        'FORM', 'INPUT', 'BUTTON',
        'TEXTAREA', 'SELECT', 'TEMPLATE',
        'IMG', 'VIDEO', 'AUDIO'
    ]);
    const htmlTagPattern =
        /<\/?(?:p|div|br|h2|h3|strong|b|em|i|u|ul|ol|li)\b[^>]*>/i;
    function decodeEntitiesOnce(value) {
        const helper = document.createElement('textarea');
        helper.innerHTML = value;
        return helper.value;
    }
    function normalizeMaterialSource(value) {
        let source = String(value ?? '').trim();
        if (!source) return '';
        // Menangani konten yang pernah tersimpan sebagai:
        // &lt;p&gt;Teks&lt;/p&gt;
        // atau &amp;lt;p&amp;gt;Teks...
        for (let i = 0; i < 4; i++) {
            if (!/&(?:amp;)*(?:lt|gt);|&#(?:0*60|0*62|x0*3c|x0*3e);/i.test(source)) {
                break;
            }
            const decoded = decodeEntitiesOnce(source);
            if (decoded === source) break;
            source = decoded;
        }
        // Materi lama mungkin memiliki tag HTML
        // sebagai teks di dalam sebuah paragraf.
        for (let i = 0; i < 3; i++) {
            if (!htmlTagPattern.test(source)) break;
            const parsed = new DOMParser().parseFromString(
                source,
                'text/html'
            );
            const visible = parsed.body.textContent.trim();
            if (
                htmlTagPattern.test(visible) &&
                visible !== source
            ) {
                source = visible;
                continue;
            }
            break;
        }
        return source;
    }
    function safeMaterialNode(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            return document.createTextNode(
                node.nodeValue || ''
            );
        }
        if (node.nodeType !== Node.ELEMENT_NODE) {
            return null;
        }
        if (blockedMaterialTags.has(node.tagName)) {
            return null;
        }
        const tag = allowedMaterialTags.has(node.tagName)
            ? node.tagName.toLowerCase()
            : 'span';
        const result = document.createElement(tag);
        // Tidak menyalin class, style, src,
        // href, onerror, onclick, dan atribut lain.
        for (const child of node.childNodes) {
            const safe = safeMaterialNode(child);
            if (safe) result.appendChild(safe);
        }
        return result;
    }
    const learningActivityTitles = new Map([
        ['ayo berpikir', 'Ayo Berpikir!'],
        ['ayo mengamati', 'Ayo Mengamati!'],
        ['ayo berdiskusi', 'Ayo Berdiskusi!'],
        ['ayo mencoba', 'Ayo Mencoba!'],
        ['ayo refleksi', 'Ayo Refleksi!'],
        ['ayo berlatih', 'Ayo Berlatih!']
    ]);
    function learningActivityTitle(node) {
        if (node.nodeType !== Node.ELEMENT_NODE) return null;
        const match = node.textContent
            .replace(/\*\*/g, '')
            .trim()
            .match(/^Ayo\s+(Berpikir|Mengamati|Berdiskusi|Mencoba|Refleksi|Berlatih)!?(?=$|\s)/i);
        if (!match) return null;
        return learningActivityTitles.get(
            `ayo ${match[1].toLocaleLowerCase('id')}`
        ) || null;
    }
    function learningActivityRemainder(node) {
        const prefix = node.textContent.match(
            /^\s*Ayo\s+(?:Berpikir|Mengamati|Berdiskusi|Mencoba|Refleksi|Berlatih)!?/i
        );
        if (!prefix || !node.textContent.slice(prefix[0].length).trim()) {
            return null;
        }
        const remainder = node.cloneNode(true);
        let charactersToRemove = prefix[0].length;
        const walker = document.createTreeWalker(
            remainder,
            NodeFilter.SHOW_TEXT
        );
        let textNode;
        while (charactersToRemove > 0 && (textNode = walker.nextNode())) {
            const count = Math.min(
                charactersToRemove,
                textNode.textContent.length
            );
            textNode.textContent = textNode.textContent.slice(count);
            charactersToRemove -= count;
        }
        return remainder;
    }
    function isLearningActivityBoundary(node) {
        if (node.nodeType !== Node.ELEMENT_NODE) return false;
        const startsNumberedSection = /^H[1-3]$/.test(node.tagName)
            && /^\s*\d+\.\s+/.test(node.textContent);
        return startsNumberedSection
            || ['FIGURE', 'IMG', 'VIDEO', 'IFRAME', 'HR', 'TABLE'].includes(node.tagName)
            || node.matches(
                '.gm-preview-media, .gm-summary-card, .sd-media-item, .sd-summary-panel'
            )
            || Boolean(node.querySelector(
                'figure, img, video, iframe, hr, table, '
                + '.gm-preview-media, .gm-summary-card, .sd-media-item, .sd-summary-panel'
            ));
    }
    function isLearningActivityContent(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            return Boolean(node.textContent.trim());
        }
        return node.nodeType === Node.ELEMENT_NODE
            && !isLearningActivityBoundary(node)
            && (/^H[1-6]$/.test(node.tagName)
                || ['P', 'OL', 'UL', 'BLOCKQUOTE', 'DIV'].includes(node.tagName));
    }
    function wrapLearningActivities(parent) {
        const nodes = [...parent.childNodes];
        const output = document.createDocumentFragment();
        for (let index = 0; index < nodes.length; index++) {
            const node = nodes[index];
            const title = learningActivityTitle(node);
            if (!title) {
                if (
                    node.nodeType === Node.ELEMENT_NODE
                    && ['DIV', 'SECTION', 'BLOCKQUOTE'].includes(node.tagName)
                ) {
                    wrapLearningActivities(node);
                }
                output.appendChild(node);
                continue;
            }
            const inlineRemainder = node.tagName === 'P'
                ? learningActivityRemainder(node)
                : null;
            const activityContent = [];
            let nextIndex = index + 1;
            while (nextIndex < nodes.length) {
                const candidate = nodes[nextIndex];
                if (
                    learningActivityTitle(candidate)
                    || isLearningActivityBoundary(candidate)
                ) {
                    break;
                }
                if (
                    candidate.nodeType === Node.TEXT_NODE
                    && !candidate.textContent.trim()
                ) {
                    activityContent.push(candidate);
                    nextIndex++;
                    continue;
                }
                if (!isLearningActivityContent(candidate)) break;
                activityContent.push(candidate);
                nextIndex++;
            }
            if (
                !inlineRemainder
                && !activityContent.some(content =>
                    content.nodeType !== Node.TEXT_NODE
                    || content.textContent.trim()
                )
            ) {
                output.appendChild(node);
                continue;
            }
            const card = el('section', 'sd-learning-activity');
            card.appendChild(el('h3', 'sd-learning-activity-title', title));
            const body = el('div', 'sd-learning-activity-body');
            if (inlineRemainder) body.appendChild(inlineRemainder);
            activityContent.forEach(content => body.appendChild(content));
            if (body.childNodes.length) card.appendChild(body);
            output.appendChild(card);
            index = nextIndex - 1;
        }
        parent.replaceChildren(output);
    }
    function renderMaterialContent(container, value, applyActivityCards = true) {
        if (!container) return;
        container.replaceChildren();
        const source = normalizeMaterialSource(value);
        if (!source) {
            container.appendChild(
                el('p', '', 'Materi Subbab belum diisi Guru.')
            );
            return;
        }
        if (htmlTagPattern.test(source)) {
            const parsed = new DOMParser().parseFromString(
                source,
                'text/html'
            );
            for (const node of parsed.body.childNodes) {
                const safe = safeMaterialNode(node);
                if (safe) container.appendChild(safe);
            }
            if (applyActivityCards) wrapLearningActivities(container);
            return;
        }
        // Kompatibilitas materi lama yang masih
        // berupa teks biasa tanpa tag HTML.
        source.split(/\r?\n\s*\r?\n/)
            .filter(Boolean)
            .forEach(paragraph => {
                const p = document.createElement('p');
                const lines = paragraph.split(/\r?\n/);
                lines.forEach((line, index) => {
                    if (index) {
                        p.appendChild(
                            document.createElement('br')
                        );
                    }
                    p.appendChild(
                        document.createTextNode(line)
                    );
                });
                container.appendChild(p);
            });
        if (applyActivityCards) wrapLearningActivities(container);
    }
    // ======================================================
    function renderSubchapterMedia(container, entries) {
        if (!container) return false;
        container.replaceChildren();
        if (!Array.isArray(entries) || entries.length === 0) {
            return false;
        }
        entries.forEach(media => {
            const item = document.createElement('section');
            item.className = 'sd-media-item';
            if (media.title) {
                const title = document.createElement('h3');
                title.className = 'sd-media-title';
                title.textContent = media.title;
                item.appendChild(title);
            }
            if (media.url && media.media_type === 'image') {
                const image = document.createElement('img');
                image.className = 'sd-media-picture';
                image.src = media.url;
                image.alt = media.title || 'Gambar pembelajaran';
                image.loading = 'lazy';
                image.addEventListener('error', () => {
                    image.remove();
                    appendUnavailableMessage(
                        item,
                        'Gambar pembelajaran tidak tersedia.'
                    );
                }, { once: true });
                item.appendChild(image);
            } else if (
                media.source === 'youtube' &&
                typeof media.embed_url === 'string' &&
                media.embed_url.startsWith(
                    'https\://www.youtube-nocookie.com/embed/'
                )
            ) {
                const frame = document.createElement('iframe');
                frame.className = 'sd-media-youtube';
                frame.title = media.title || 'Video YouTube pembelajaran';
                frame.loading = 'lazy';
                frame.allowFullscreen = true;
                frame.referrerPolicy = 'strict-origin-when-cross-origin';
                frame.allow =
                    'accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                frame.src = media.embed_url;
                item.appendChild(frame);
            } else if (
                media.url &&
                media.media_type === 'video' &&
                media.source !== 'youtube'
            ) {
                const video = document.createElement('video');
                video.className = 'sd-media-video';
                video.controls = true;
                video.preload = 'metadata';
                video.playsInline = true;
                const source = document.createElement('source');
                source.src = media.url;
                source.type = 'video/mp4';
                video.appendChild(source);
                video.addEventListener('error', () => {
                    video.remove();
                    appendUnavailableMessage(
                        item,
                        'Video tidak dapat dimuat. Periksa kembali berkas media.'
                    );
                }, { once: true });
                item.appendChild(video);
            } else {
                appendUnavailableMessage(
                    item,
                    'Berkas media tidak tersedia.'
                );
            }
            if (media.caption) {
                const caption = document.createElement('p');
                caption.className = 'sd-media-caption';
                caption.textContent = media.caption;
                item.appendChild(caption);
            }
            container.appendChild(item);
        });
        return true;
    }
    function appendUnavailableMessage(container, message) {
        const notice = document.createElement('p');
        notice.className = 'sd-media-unavailable';
        notice.setAttribute('role', 'status');
        notice.textContent = message;
        container.appendChild(notice);
    }
    // SALUANG_SISWA_PREVIEW_SYNC_V1: kartu informasi berbasis konten Guru.
    // Teks ditambahkan melalui textContent agar tidak menjalankan HTML mentah.
    const activityNames = [
        'Ayo Berpikir!', 'Ayo Mengamati!', 'Ayo Berdiskusi!',
        'Ayo Mencoba!', 'Ayo Refleksi!', 'Ayo Berlatih!'
    ];
    function isStudentActivity(title) {
        return activityNames.some(name =>
            name.toLocaleLowerCase('id') === String(title || '').trim().toLocaleLowerCase('id')
        );
    }
    function renderStudentActivity(section, payload) {
        section.classList.add('sd-content-block-activity');
        const card = el('section', 'sd-learning-activity sd-learning-activity-explicit');
        card.appendChild(el('h3', 'sd-learning-activity-title', payload.title));
        const body = el('div', 'sd-learning-activity-body');
        renderMaterialContent(body, payload.content || '');
        card.appendChild(body);
        section.appendChild(card);
    }
    function summaryContentToText(value) {
        if (!/<\/?(?:p|div|h[1-6]|ol|ul|li|br|strong|b|em|i)\b/i.test(value)) {
            return value;
        }
        const parsed = new DOMParser().parseFromString(value, 'text/html');
        const inline = node => {
            if (node.nodeType === Node.TEXT_NODE) return node.nodeValue || '';
            if (node.nodeType !== Node.ELEMENT_NODE) return '';
            const content = [...node.childNodes].map(inline).join('');
            if (node.tagName === 'BR') return '\n';
            if (['B', 'STRONG'].includes(node.tagName)) return `**${content}**`;
            if (['I', 'EM'].includes(node.tagName)) return `*${content}*`;
            return content;
        };
        const block = node => {
            if (node.nodeType === Node.TEXT_NODE) return node.nodeValue || '';
            if (node.nodeType !== Node.ELEMENT_NODE) return '';
            const content = [...node.childNodes].map(block).join('');
            switch (node.tagName) {
                case 'H1':
                case 'H2':
                    return `## ${content.trim()}\n\n`;
                case 'H3':
                case 'H4':
                case 'H5':
                case 'H6':
                    return `### ${content.trim()}\n\n`;
                case 'P':
                case 'DIV':
                    return `${content.trim()}\n\n`;
                case 'UL':
                    return [...node.children]
                        .filter(child => child.tagName === 'LI')
                        .map(child => `- ${inline(child).trim()}\n`)
                        .join('') + '\n';
                case 'OL':
                    return [...node.children]
                        .filter(child => child.tagName === 'LI')
                        .map((child, index) => `${index + 1}. ${inline(child).trim()}\n`)
                        .join('') + '\n';
                default:
                    return inline(node);
            }
        };
        return [...parsed.body.childNodes]
            .map(block)
            .join('')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }
    // Kesimpulan Siswa: struktur dan gaya identik dengan pratinjau Guru.
    // Semua konten berasal dari database Guru; tidak mengubah data tersimpan.
    function renderStudentCallout(section, payload) {
        const card = el('section', 'gm-summary-card');
        const header = el('header', 'gm-summary-header');
        header.appendChild(el('h4', '', payload?.title || 'Informasi / Kesimpulan'));
        card.appendChild(header);
        const body = el('div', 'gm-summary-body');
        const raw = String(payload?.content || '').replace(/\r\n?/g, '\n').trim();
        const lines = summaryContentToText(raw).split('\n');
        let items = [];
        const flushItems = () => {
            if (!items.length) return;
            const grid = el('div', 'gm-summary-grid');
            items.forEach(item => grid.appendChild(item));
            body.appendChild(grid);
            items = [];
        };
        const inline = (container, value) => {
            String(value || '').split(/(\*\*[^*]+\*\*)/g).forEach(part => {
                if (part.startsWith('**') && part.endsWith('**')) {
                    container.appendChild(el('strong', '', part.slice(2, -2)));
                } else if (part) {
                    container.appendChild(document.createTextNode(part));
                }
            });
        };
        for (const source of lines) {
            const line = source.trim();
            if (!line) continue;
            const numbered = line.match(/^(?:\*\*)?(\d+)\.\s+(.+)$/);
            if (numbered) {
                const remainder = numbered[2].replace(/\*\*$/, '');
                const label = remainder.match(/^\*\*(.+?):?\*\*\s*(.*)$/);
                const heading = label ? label[1].replace(/:$/, '') : remainder.split(':')[0].replace(/\*\*/g, '').trim();
                const description = label ? label[2] : (remainder.includes(':') ? remainder.slice(remainder.indexOf(':') + 1).trim() : '');
                const item = el('article', 'gm-summary-item');
                item.appendChild(el('span', 'gm-summary-number', numbered[1]));
                const content = el('div');
                const h = el('h5');
                inline(h, heading);
                content.appendChild(h);
                if (description) {
                    const p = el('p');
                    inline(p, description);
                    content.appendChild(p);
                }
                item.appendChild(content);
                items.push(item);
                continue;
            }
            flushItems();
            const heading = line.match(/^#{2,3}\s+(.+)$/);
            const knownHeading = /^(?:RANGKUMAN KARAKTERISTIK MAKHLUK HIDUP|MAKHLUK HIDUP DI SEKITAR KITA|KESIMPULAN|AYO REFLEKSI!?|AYO MENGINGAT KEMBALI!?)$/i.test(line.replace(/\*\*/g, ''));
            if (heading || knownHeading) {
                const h = el('h4', 'gm-summary-heading');
                inline(h, heading ? heading[1] : line);
                body.appendChild(h);
            } else {
                const p = el('p', 'gm-summary-paragraph');
                inline(p, line);
                body.appendChild(p);
            }
        }
        flushItems();
        if (!body.childNodes.length) body.appendChild(el('p', '', 'Belum ada isi kesimpulan.'));
        card.appendChild(body);
        section.appendChild(card);
    }
    function renderMaterialBlocks(container, blocks) {
        if (!container || !Array.isArray(blocks)) return false;
        container.replaceChildren();
        if (!blocks.length) {
            container.appendChild(
                el('p', '', 'Materi Subbab belum diisi Guru.')
            );
            return false;
        }
        blocks.forEach(block => {
            if (block.type === 'text') {
                const section = el('section', 'sd-content-block');
                renderMaterialContent(
                    section,
                    block.payload?.content || ''
                );
                container.appendChild(section);
                return;
            }
            const section = el('section', 'sd-content-block');
            if (block.type === 'callout') {
                if (isStudentActivity(block.payload?.title)) {
                    renderStudentActivity(section, block.payload || {});
                } else {
                    renderStudentCallout(section, block.payload || {});
                }
            } else if (
                (block.type === 'image' || block.type === 'video') &&
                block.media
            ) {
                renderSubchapterMedia(section, [{
                    ...block.media,
                    title: block.payload?.title || block.media.title,
                    caption: block.payload?.caption || block.media.caption
                }]);
            } else {
                appendUnavailableMessage(
                    section,
                    'Blok materi tidak dapat ditampilkan.'
                );
            }
            container.appendChild(section);
        });
        return true;
    }
    window.SaluangStudentMaterial = {
        configure(elementFactory) { el = elementFactory; },
        renderMaterialContent,
        renderSubchapterMedia,
        renderMaterialBlocks,
    };
})();
