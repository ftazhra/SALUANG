

/* SALUANG - Kelola Materi Guru | public/js/guru-materi.js */

document.addEventListener('DOMContentLoaded', () => {

  'use strict';



  const $ = id => document.getElementById(id);

  const page = $('guruMaterialPage');

  if (!page) return;



  const listScreen = $('guruMaterialList'), detailScreen = $('guruMaterialDetail');

  const chapterList = $('gmChapterList'), subchapterList = $('gmSubchapterList');

  const chapterModal = $('gmChapterModal'), subchapterModal = $('gmSubchapterModal');

  const deleteModal = $('gmDeleteModal'), chapterForm = $('gmChapterForm');

  const subchapterForm = $('gmSubchapterForm'), objectivesList = $('gmObjectivesList');

  const editor = $('gmRichContentEditor'), toolbar = $('gmRichToolbar');

  const contentField = $('gmSubchapterContent'), toast = $('gmToast');

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';



  let chapters = [];

  let imageLibrary = [];

  let videoLibrary = [];

  let activeImage = null;

  let activeVideo = null;

  let imagePreviewUrl = null;

  let videoPreviewUrl = null;

  let selectedChapter = null;

  let contentBlocks = [];

  let availableBlockMedia = [];

  let activeBlockKey = null;

  let nextBlockKey = 1;

  let pendingDelete = null;

  let subchapterFormDirty = false;

  let toastTimer;



  // ========================================================

  // 01. API LARAVEL

  // ========================================================



  async function api(url, method = 'GET', data = null) {

    const options = {

      method,

      credentials: 'same-origin',

      headers: {

        Accept: 'application/json',

        'X-Requested-With': 'XMLHttpRequest',

        'X-CSRF-TOKEN': csrf

      }

    };



    if (data !== null) {

      if (data instanceof FormData) {

        options.body = data;

      } else {

        options.headers['Content-Type'] = 'application/json';

        options.body = JSON.stringify(data);

      }

    }



    const response = await fetch(url, options);

    let result;



    try {

      result = await response.json();

    } catch {

      throw new Error('Server tidak mengembalikan JSON yang valid.');

    }



    if (!response.ok) {

      const first = result.errors

        ? Object.values(result.errors).flat()[0]

        : null;



      throw new Error(

        first ||

        result.message ||

        'Permintaan gagal.'

      );

    }



    return result;

  }



  // ========================================================

  // 02. UTILITAS

  // ========================================================



  const chapterUrl = id =>

    `/guru/chapters/${encodeURIComponent(id)}`;



  const subchapterUrl = (chapterId, id = null) =>

    `${chapterUrl(chapterId)}/subchapters${id === null ? '' : '/' + encodeURIComponent(id)

    }`;



  const escapeHTML = value =>

    String(value ?? '').replace(/[&<>"']/g, c => ({

      '&': '&amp;',

      '<': '&lt;',

      '>': '&gt;',

      '"': '&quot;',

      "'": '&#39;'

    })[c]);



  function notify(message, error = false) {

    if (!toast) return;



    clearTimeout(toastTimer);



    toast.textContent = message;

    toast.classList.toggle('is-error', error);

    toast.hidden = false;



    toastTimer = setTimeout(() => {

      toast.hidden = true;

    }, 3500);

  }



  function closeModals() {

    if (

      subchapterModal

      && !subchapterModal.hidden

      && subchapterFormDirty

      && !window.confirm(

        'Perubahan materi belum disimpan. Tutup tanpa menyimpan?'

      )

    ) {

      return false;

    }



    [chapterModal, subchapterModal, deleteModal].forEach(m => {

      if (m) m.hidden = true;

    });



    return true;

  }



  function openModal(modal) {

    if (!closeModals()) return;



    if (modal) {

      modal.hidden = false;

    }

  }



  // ========================================================

  // 03. FORMAT HTML MATERI

  // ========================================================



  // Hanya format tulisan yang boleh masuk preview/editor.

  // Semua atribut HTML dibuang.

  //

  // PENTING:

  // Ini pengamanan di browser Guru.

  // Sanitasi di server Laravel tetap diperlukan.



  const allowed = new Set([

    'P',

    'BR',

    'H2',

    'H3',

    'STRONG',

    'B',

    'EM',

    'I',

    'U',

    'OL',

    'UL',

    'LI',

    'DIV'

  ]);



  const blocked = new Set([

    'SCRIPT',

    'STYLE',

    'IFRAME',

    'OBJECT',

    'EMBED',

    'SVG',

    'MATH',

    'FORM',

    'INPUT',

    'BUTTON',

    'TEXTAREA',

    'SELECT',

    'TEMPLATE',

    'IMG',

    'VIDEO',

    'AUDIO'

  ]);



  const formatPattern =

    /<\/?(?:p|div|br|h2|h3|strong|b|em|i|u|ol|ul|li)\b[^>]*>/i;



  function decodeEntitiesOnce(value) {

    const box = document.createElement('textarea');



    box.innerHTML = value;



    return box.value;

  }



  function decodeLegacy(value) {

    let raw = String(value ?? '').trim();



    // Menangani konten lama seperti:

    // &lt;p&gt;Materi&lt;/p&gt;

    // &amp;lt;p&amp;gt;Materi&amp;lt;/p&amp;gt;



    for (let i = 0; i < 3; i++) {

      if (

        !/(?:&lt;|&gt;|&#0*60;|&#x0*3c;|&amp;lt;|&amp;gt;)/i.test(raw)

      ) {

        break;

      }



      const next = decodeEntitiesOnce(raw);



      if (next === raw) break;



      raw = next;

    }



    return raw;

  }



  function cleanHTML(raw) {

    const parsed = new DOMParser().parseFromString(

      raw,

      'text/html'

    );



    const out = document.createElement('div');



    function convert(node) {

      if (node.nodeType === Node.TEXT_NODE) {

        return document.createTextNode(

          node.nodeValue || ''

        );

      }



      if (

        node.nodeType !== Node.ELEMENT_NODE ||

        blocked.has(node.tagName)

      ) {

        return null;

      }



      const tag = allowed.has(node.tagName)

        ? node.tagName.toLowerCase()

        : 'span';



      const safe = document.createElement(tag);



      [...node.childNodes].forEach(child => {

        const converted = convert(child);



        if (converted) {

          safe.appendChild(converted);

        }

      });



      return safe;

    }



    [...parsed.body.childNodes].forEach(node => {

      const safe = convert(node);



      if (safe) {

        out.appendChild(safe);

      }

    });



    return out.innerHTML;

  }



  function materialHTML(value) {

    let raw = decodeLegacy(value);



    if (!raw) return '';



    // PERBAIKAN UTAMA:

    //

    // Konten lama bisa berisi tag HTML yang

    // tersimpan sebagai teks di dalam paragraf.

    //

    // Contoh:

    // <p>&lt;p&gt;Materi&lt;/p&gt;</p>

    //

    // Fungsi ini mencoba mengembalikannya menjadi

    // elemen paragraf sungguhan.



    for (let i = 0; i < 3; i++) {

      if (!formatPattern.test(raw)) break;



      const cleaned = cleanHTML(raw);



      const probe = document.createElement('div');

      probe.innerHTML = cleaned;



      const visible = probe.textContent.trim();



      if (

        formatPattern.test(visible) &&

        visible !== raw

      ) {

        raw = decodeLegacy(visible);

        continue;

      }



      return cleaned;

    }



    if (formatPattern.test(raw)) {

      return cleanHTML(raw);

    }



    // Kompatibilitas dengan materi lama

    // yang masih berbentuk teks biasa.



    const pieces = raw

      .split(/\r?\n\s*\r?\n/)

      .filter(Boolean);



    return pieces.map(p =>

      '<p>' +

      escapeHTML(p).replace(/\r?\n/g, '<br>') +

      '</p>'

    ).join('');

  }



  function htmlToEditorText(value) {

    const parsed = new DOMParser().parseFromString(

      materialHTML(value),

      'text/html'

    );



    function inline(node) {

      if (node.nodeType === Node.TEXT_NODE) {

        return node.nodeValue || '';

      }

      if (node.nodeType !== Node.ELEMENT_NODE) return '';



      const content = [...node.childNodes].map(inline).join('');

      switch (node.tagName) {

        case 'BR': return '\n';

        case 'B':

        case 'STRONG': return `\*\*${content}\*\*`;

        case 'I':

        case 'EM': return `\*${content}\*`;

        case 'U': return `\_\_${content}\_\_`;

        default: return content;

      }

    }



    function block(node) {

      if (node.nodeType === Node.TEXT_NODE) {

        return node.nodeValue || '';

      }

      if (node.nodeType !== Node.ELEMENT_NODE) return '';



      const content = [...node.childNodes].map(block).join('');

      switch (node.tagName) {

        case 'H2': return `## ${content.trim()}\n\n`;

        case 'H3': return `### ${content.trim()}\n\n`;

        case 'P':

        case 'DIV': return `${content.trim()}\n\n`;

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

        default: return inline(node);

      }

    }



    return [...parsed.body.childNodes]

      .map(block)

      .join('')

      .replace(/\n{3,}/g, '\n\n')

      .trim();

  }



  function editorTextToHTML(value) {

    const lines = String(value ?? '').replace(/\r\n?/g, '\n').split('\n');

    const output = [];

    let index = 0;



    const inline = text => escapeHTML(text)

      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')

      .replace(/\_\_(.+?)\_\_/g, '<u>$1</u>')

      .replace(/\*([^*\n]+)\*/g, '<em>$1</em>');

    const listType = line => /^\s*(?:[-\*]|\d+\.)\s+/.test(line);



    while (index < lines.length) {

      const line = lines[index].trim();

      if (!line) {

        index++;

        continue;

      }



      const heading = line.match(/^(#{2,3})\s+(.+)$/);

      if (heading) {

        const tag = heading[1].length === 2 ? 'h2' : 'h3';

        output.push(`<${tag}>${inline(heading[2])}</${tag}>`);

        index++;

        continue;

      }



      const listMatch = line.match(/^\s*([-\*]|\d+\.)\s+/);

      if (listMatch) {

        const ordered = /^\d/.test(listMatch[1]);

        const tag = ordered ? 'ol' : 'ul';

        const items = [];

        while (index < lines.length) {

          const item = lines[index].match(/^\s*(?:[-\*]|\d+\.)\s+(.+)$/);

          if (!item || /^\d/.test(item[0].trim()) !== ordered) break;

          items.push(`<li>${inline(item[1])}</li>`);

          index++;

        }

        output.push(`<${tag}>${items.join('')}</${tag}>`);

        continue;

      }



      const paragraph = [];

      while (

        index < lines.length

        && lines[index].trim()

        && !/^(#{2,3})\s+/.test(lines[index].trim())

        && !listType(lines[index])

      ) {

        paragraph.push(lines[index].trim());

        index++;

      }

      output.push(`<p>${paragraph.map(inline).join('<br>')}</p>`);

    }



    return output.join('');

  }



  // ========================================================

  // 04. EDITOR MATERI GURU

  // ========================================================



  function syncEditor() {

    if (editor && contentField) {

      contentField.value = cleanHTML(

        editorTextToHTML(editor.value)

      );

    }

  }



  function loadEditor(value = '') {

    if (!contentField) return;



    if (editor) {

      editor.value = htmlToEditorText(value);

      syncEditor();

    } else {

      contentField.value = String(value ?? '');

    }

  }



  function normalizeContentBlock(block) {

    const payload = block?.payload || {};



    return {

      clientKey: `content-block-${nextBlockKey++}`,

      id: block?.id ?? null,

      type: block?.type || 'text',

      content: payload.content || '',

      title: payload.title || '',

      mediaId: block?.media_id ?? block?.media?.id ?? null,

      media: block?.media || null,

      caption: payload.caption || block?.media?.caption || '',

      source: block?.media_id || block?.media?.id ? 'existing' : 'upload',

      youtubeUrl: '',

      file: null

    };

  }



  function activeContentBlock() {

    return contentBlocks.find(

      block => block.clientKey === activeBlockKey

    ) || null;

  }



  function commitActiveContentBlock() {

    const block = activeContentBlock();

    if (!block) return;



    if (block.type === 'text') {

      syncEditor();

      block.content = contentField?.value.trim() || '';

    } else if (block.type === 'callout') {

      block.title = $('gmCalloutTitle')?.value.trim() || '';

      block.content = $('gmCalloutContent')?.value.trim() || '';

    } else {

      block.source = $('gmBlockMediaSource')?.value || block.source;

      block.title = $('gmBlockMediaTitle')?.value.trim() || '';

      block.caption = $('gmBlockMediaCaption')?.value.trim() || '';

      block.youtubeUrl = $('gmBlockYoutubeUrl')?.value.trim() || '';

      if (block.source === 'existing') {

        block.mediaId = Number($('gmBlockExistingMedia')?.value) || null;

        block.media = availableBlockMedia.find(

          media => Number(media.id) === block.mediaId

        ) || null;

      } else {

        block.mediaId = null;

      }

    }

  }



  function renderContentBlocks() {

    const list = $('gmContentBlockList');

    const empty = $('gmContentBlockEmpty');

    const placeholder = $('gmContentBlockPlaceholder');

    if (!list || !empty) return;



    list.replaceChildren();

    empty.hidden = contentBlocks.length > 0;

    if (placeholder) {

      placeholder.hidden = !contentBlocks.length || Boolean(activeBlockKey);

    }



    contentBlocks.forEach((block, index) => {

      const row = document.createElement('li');

      row.className = 'gm-content-block-card';

      row.classList.toggle(

        'is-selected',

        block.clientKey === activeBlockKey

      );



      const summary = document.createElement('div');

      summary.className = 'gm-content-block-summary';



      const heading = document.createElement('strong');

      heading.textContent = `${index + 1}. ${({

        text: 'Teks penjelasan',

        image: 'Gambar pembelajaran',

        video: 'Video pembelajaran',

        callout: 'Informasi / Kesimpulan'

      }[block.type] || 'Blok materi')}`;



      const detail = document.createElement('span');

      if (block.type === 'text' || block.type === 'callout') {

        const parsed = new DOMParser().parseFromString(

          block.content || '',

          'text/html'

        );

        detail.textContent = parsed.body.textContent.trim()

          || 'Belum ada isi.';

      } else {

        detail.textContent = block.file?.name ||

          block.title ||

          block.media?.title ||

          (block.source === 'youtube'

            ? block.youtubeUrl || 'Tautan YouTube'

            : block.mediaId

              ? 'Media tersimpan'

              : 'Pilih berkas atau media Subbab');

      }



      summary.append(heading, detail);



      const actions = document.createElement('div');

      actions.className = 'gm-content-block-actions';



      const edit = document.createElement('button');

      edit.type = 'button';

      edit.className = 'gm-btn gm-btn-outline';

      edit.dataset.blockAction = 'edit';

      edit.dataset.blockIndex = String(index);

      edit.textContent = block.type === 'text' || block.type === 'callout'

        ? 'Edit isi'

        : 'Edit media';



      const up = document.createElement('button');

      up.type = 'button';

      up.className = 'gm-btn gm-btn-outline';

      up.dataset.blockAction = 'up';

      up.dataset.blockIndex = String(index);

      up.textContent = 'Naik';

      up.disabled = index === 0;



      const down = document.createElement('button');

      down.type = 'button';

      down.className = 'gm-btn gm-btn-outline';

      down.dataset.blockAction = 'down';

      down.dataset.blockIndex = String(index);

      down.textContent = 'Turun';

      down.disabled = index === contentBlocks.length - 1;



      const remove = document.createElement('button');

      remove.type = 'button';

      remove.className = 'gm-btn gm-btn-danger';

      remove.dataset.blockAction = 'delete';

      remove.dataset.blockIndex = String(index);

      remove.textContent = 'Hapus';



      actions.append(edit, up, down, remove);

      row.append(summary, actions);

      list.appendChild(row);

    });

  }



  function selectContentBlock(block) {

    commitActiveContentBlock();

    activeBlockKey = block?.clientKey || null;



    const calloutEditor = $('gmCalloutEditor');

    const mediaEditor = $('gmBlockMediaEditor');

    const textEditor = $('gmTextBlockEditor');

    if (block?.type === 'text') {

      loadEditor(block.content);

      if (textEditor) textEditor.hidden = false;

      if (calloutEditor) calloutEditor.hidden = true;

      if (mediaEditor) mediaEditor.hidden = true;

    } else if (block?.type === 'callout') {

      loadEditor('');

      if (textEditor) textEditor.hidden = true;

      $('gmCalloutTitle').value = block.title || '';

      $('gmCalloutContent').value = block.content || '';

      if (calloutEditor) calloutEditor.hidden = false;

      if (mediaEditor) mediaEditor.hidden = true;

    } else if (block?.type === 'image' || block?.type === 'video') {

      loadEditor('');

      if (textEditor) textEditor.hidden = true;

      if (calloutEditor) calloutEditor.hidden = true;

      if (mediaEditor) mediaEditor.hidden = false;

      updateBlockMediaEditor(block);

    } else {

      loadEditor('');

      if (textEditor) textEditor.hidden = true;

      if (calloutEditor) calloutEditor.hidden = true;

      if (mediaEditor) mediaEditor.hidden = true;

    }



    renderContentBlocks();

  }



  function updateBlockMediaEditor(block) {

    const type = block.type;

    const sourceSelect = $('gmBlockMediaSource');

    const existingField = $('gmBlockExistingMediaField');

    const uploadField = $('gmBlockUploadField');

    const youtubeField = $('gmBlockYoutubeField');

    const existingSelect = $('gmBlockExistingMedia');

    const fileInput = $('gmBlockMediaFile');



    sourceSelect.replaceChildren();

    const sources = type === 'video'

      ? [['upload', 'Unggah MP4'], ['youtube', 'YouTube'], ['existing', 'Pilih media tersimpan']]

      : [['upload', 'Unggah gambar'], ['existing', 'Pilih media tersimpan']];

    sources.forEach(([value, label]) => {

      const option = document.createElement('option');

      option.value = value;

      option.textContent = label;

      sourceSelect.appendChild(option);

    });



    existingSelect.replaceChildren();

    const available = availableBlockMedia.filter(

      media => media.media_type === type

        && !contentBlocks.some(other =>

          other.clientKey !== block.clientKey

          && Number(other.mediaId) === Number(media.id)

        )

    );

    available.forEach(media => {

      const option = document.createElement('option');

      option.value = String(media.id);

      option.textContent = media.title || `${type === 'image' ? 'Gambar' : 'Video'} ${media.id}`;

      existingSelect.appendChild(option);

    });



    const source = block.source || (block.mediaId ? 'existing' : 'upload');

    block.source = source;

    sourceSelect.value = source;

    existingField.hidden = source !== 'existing';

    uploadField.hidden = source !== 'upload';

    youtubeField.hidden = source !== 'youtube';

    existingSelect.value = block.mediaId ? String(block.mediaId) : '';

    $('gmBlockYoutubeUrl').value = block.youtubeUrl || '';

    $('gmBlockMediaTitle').value = block.title || block.media?.title || '';

    $('gmBlockMediaCaption').value = block.caption || block.media?.caption || '';

    $('gmBlockMediaHelp').textContent = type === 'image'

      ? 'Pilih gambar yang akan tampil pada posisi blok ini.'

      : 'Pilih berkas MP4, tautan YouTube, atau video tersimpan.';

    $('gmBlockMediaFileHelp').textContent = type === 'image'

      ? 'JPG, PNG, atau WebP · maksimal 5 MB.'

      : 'MP4 · maksimal 30 MB.';

    fileInput.accept = type === 'image'

      ? '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp'

      : '.mp4,video/mp4';

    fileInput.value = '';

  }



  $('gmContentBlockList')?.addEventListener('click', event => {

    const button = event.target.closest?.('[data-block-action]');

    if (!button) return;



    const index = Number(button.dataset.blockIndex);

    const block = contentBlocks[index];

    if (!block) return;



    const action = button.dataset.blockAction;

    if (action === 'edit') {

      selectContentBlock(block);

      return;

    }



    commitActiveContentBlock();

    if (action === 'delete') {

      if (!window.confirm('Hapus blok ini dari susunan materi? Media lama tetap tersimpan.')) {

        return;

      }



      contentBlocks.splice(index, 1);

      subchapterFormDirty = true;

      if (activeBlockKey === block.clientKey) {

        activeBlockKey = null;

        loadEditor('');

        $('gmTextBlockEditor').hidden = true;

        $('gmCalloutEditor').hidden = true;

        $('gmBlockMediaEditor').hidden = true;

      }

    } else {

      const nextIndex = action === 'up' ? index - 1 : index + 1;

      if (nextIndex < 0 || nextIndex >= contentBlocks.length) return;

      [contentBlocks[index], contentBlocks[nextIndex]] =

        [contentBlocks[nextIndex], contentBlocks[index]];

      subchapterFormDirty = true;

    }



    renderContentBlocks();

  });



  $('gmAddContentBlock')?.addEventListener('click', () => {

    commitActiveContentBlock();

    subchapterFormDirty = true;



    const type = $('gmContentBlockType').value;

    if (type === 'image' || type === 'video') {

      contentBlocks.push({

        clientKey: `content-block-${nextBlockKey++}`,

        id: null,

        type,

        content: '',

        title: '',

        mediaId: null,

        media: null,

        caption: '',

        source: 'upload',

        youtubeUrl: '',

        file: null

      });

    } else {

      contentBlocks.push({

        clientKey: `content-block-${nextBlockKey++}`,

        id: null,

        type,

        content: '',

        title: type === 'callout' ? 'Kesimpulan' : '',

        mediaId: null,

        media: null

      });

    }



    selectContentBlock(contentBlocks[contentBlocks.length - 1]);

  });



  ['gmCalloutTitle', 'gmCalloutContent'].forEach(id => {

    $(id)?.addEventListener('input', () => {

      const block = activeContentBlock();

      if (block?.type === 'callout') {

        block.title = $('gmCalloutTitle').value.trim();

        block.content = $('gmCalloutContent').value.trim();

        renderContentBlocks();

      }

    });



    subchapterForm?.addEventListener('input', () => {

      subchapterFormDirty = true;

    });

    subchapterForm?.addEventListener('change', () => {

      subchapterFormDirty = true;

    });



    $('gmBlockMediaSource')?.addEventListener('change', event => {

      const block = activeContentBlock();

      if (!block || !['image', 'video'].includes(block.type)) return;



      block.source = event.currentTarget.value;

      if (block.source === 'existing') {

        block.mediaId = Number($('gmBlockExistingMedia').value) || null;

      } else {

        block.mediaId = null;

      }

      if (block.source !== 'upload') {

        block.file = null;

      }

      updateBlockMediaEditor(block);

      renderContentBlocks();

    });



    $('gmBlockExistingMedia')?.addEventListener('change', event => {

      const block = activeContentBlock();

      if (!block) return;



      block.mediaId = Number(event.currentTarget.value) || null;

      block.media = availableBlockMedia.find(

        media => Number(media.id) === block.mediaId

      ) || null;

      block.title = block.media?.title || '';

      block.caption = block.media?.caption || '';

      $('gmBlockMediaTitle').value = block.title;

      $('gmBlockMediaCaption').value = block.caption;

      renderContentBlocks();

    });



    $('gmBlockMediaFile')?.addEventListener('change', event => {

      const block = activeContentBlock();

      if (!block) return;



      block.file = event.currentTarget.files?.[0] || null;

      block.source = 'upload';

      renderContentBlocks();

    });



    ['gmBlockMediaTitle', 'gmBlockMediaCaption', 'gmBlockYoutubeUrl'].forEach(id => {

      $(id)?.addEventListener('input', () => {

        const block = activeContentBlock();

        if (!block) return;



        block.title = $('gmBlockMediaTitle').value.trim();

        block.caption = $('gmBlockMediaCaption').value.trim();

        block.youtubeUrl = $('gmBlockYoutubeUrl').value.trim();

        renderContentBlocks();

      });

    });

  });



  // Semua perilaku editor diurus file JS ini.

  // Script editor lama di Blade harus dinonaktifkan.



  if (editor && toolbar && contentField) {

    toolbar.addEventListener('mousedown', e => {

      if (

        e.target.closest(

          'button[data-gm-command],button[data-gm-block]'

        )

      ) {

        e.preventDefault();

      }

    });



    toolbar.addEventListener('click', e => {

      const button = e.target.closest(

        'button[data-gm-command],button[data-gm-block]'

      );



      if (!button) return;



      e.preventDefault();

      const cmd = button.dataset.gmCommand;

      const block = button.dataset.gmBlock;

      const value = editor.value;

      const selectionStart = editor.selectionStart;

      const selectionEnd = editor.selectionEnd;



      if (block || cmd === 'insertOrderedList' || cmd === 'insertUnorderedList') {

        const lineStart = value.lastIndexOf('\n', Math.max(0, selectionStart - 1)) + 1;

        const nextLineBreak = value.indexOf('\n', selectionEnd);

        const lineEnd = nextLineBreak === -1 ? value.length : nextLineBreak;

        const selectedLines = value.slice(lineStart, lineEnd).split('\n');

        const formattedLines = selectedLines.map(line => {

          const content = line.replace(/^\s*(?:#{1,3}\s+|[-\*]\s+|\d+\.\s+)/, '');

          if (block === 'H2') return `## ${content}`;

          if (block === 'H3') return `### ${content}`;

          if (block === 'P') return content;

          if (cmd === 'insertOrderedList') return `1. ${content}`;

          return `- ${content}`;

        });

        editor.setRangeText(

          formattedLines.join('\n'),

          lineStart,

          lineEnd,

          'select'

        );

      } else if (cmd === 'removeFormat') {

        const selection = value.slice(selectionStart, selectionEnd);

        const plain = selection

          .replace(/^\s*(?:#{1,3}\s+|[-\*]\s+|\d+\.\s+)/gm, '')

          .replace(/\*\*|__|\*/g, '');

        editor.setRangeText(plain, selectionStart, selectionEnd, 'select');

      } else {

        const wrappers = {

          bold: ['\*\*', '\*\*'],

          italic: ['\*', '\*'],

          underline: ['\_\_', '\_\_']

        }[cmd];



        if (wrappers) {

          const selection = value.slice(selectionStart, selectionEnd);

          const formatted = `${wrappers[0]}${selection}${wrappers[1]}`;

          editor.setRangeText(

            formatted,

            selectionStart,

            selectionEnd,

            'select'

          );

          if (!selection) {

            editor.setSelectionRange(

              selectionStart + wrappers[0].length,

              selectionStart + wrappers[0].length

            );

          }

        }

      }



      editor.focus();

      syncEditor();

    });



    editor.addEventListener('input', () => {

      syncEditor();

      const block = activeContentBlock();

      if (block?.type === 'text') {

        block.content = contentField.value.trim();

        renderContentBlocks();

      }

    });

  }



  // ========================================================

  // 05. AMBIL DAFTAR BAB

  // ========================================================



  async function loadChapters() {

    if (!chapterList) return;



    chapterList.textContent = 'Memuat daftar Bab...';



    try {

      const result = await api('/guru/chapters');



      chapters = Array.isArray(result.chapters)

        ? result.chapters

        : [];

      imageLibrary = Array.isArray(result.image_library)

        ? result.image_library

        : [];

      videoLibrary = Array.isArray(result.video_library)

        ? result.video_library

        : [];



      updateMediaCatalogs();



      renderChapters();

    } catch (e) {

      chapterList.textContent = e.message;

      notify(e.message, true);

    }

  }



  function renderChapters() {

    if (!chapters.length) {

      chapterList.innerHTML = `

        <div class="gm-empty">

          Belum ada Bab. Klik Tambah Bab.

        </div>

      `;

      return;

    }



    chapterList.innerHTML = chapters.map(c => `

      <article class="gm-chapter-card">

        <span class="gm-chapter-label">

          BAB ${escapeHTML(c.nomor_bab)}

        </span>



        <h2>${escapeHTML(c.judul)}</h2>



        <p>

          ${escapeHTML(

      c.deskripsi || 'Belum ada deskripsi Bab.'

    )}

        </p>



        <div class="gm-chapter-meta">

          ${(c.subchapters || []).length} Subbab ·

          ${c.is_published

        ? 'Dipublikasikan'

        : 'Draft'}

        </div>



        <div class="gm-chapter-actions">

          <button

            type="button"

            class="gm-btn gm-btn-primary"

            data-gm-action="view-chapter"

            data-id="${Number(c.id)}"

          >

            <i class="bi bi-eye"></i>

            Lihat Selengkapnya

          </button>



          <button

            type="button"

            class="gm-btn gm-btn-outline"

            data-gm-action="edit-chapter"

            data-id="${Number(c.id)}"

          >

            <i class="bi bi-pencil-square"></i>

            Edit

          </button>



          <button

            type="button"

            class="gm-btn gm-btn-danger"

            data-gm-action="delete-chapter"

            data-id="${Number(c.id)}"

          >

            <i class="bi bi-trash3"></i>

            Hapus

          </button>

        </div>

      </article>

    `).join('');

  }



  // ========================================================

  // 06. DETAIL BAB

  // ========================================================



  async function openChapter(id) {

    try {

      const result = await api(

        chapterUrl(id)

      );



      selectedChapter = result.chapter;



      $('gmDetailNumber').textContent =

        `BAB ${selectedChapter.nomor_bab}`;



      $('gmDetailTitle').textContent =

        selectedChapter.judul;



      $('gmDetailDescription').textContent =

        selectedChapter.deskripsi ||

        'Belum ada deskripsi Bab.';



      $('gmDetailIntroduction').textContent =

        selectedChapter.pengantar ||

        'Belum ada pengantar Bab.';



      let objectives =

        selectedChapter.tujuan_pembelajaran || [];



      if (typeof objectives === 'string') {

        try {

          objectives = JSON.parse(objectives);

        } catch {

          objectives = [];

        }

      }



      if (!Array.isArray(objectives)) {

        objectives = [];

      }



      const list = $('gmDetailObjectives');

      list.replaceChildren();



      (

        objectives.length

          ? objectives

          : ['Belum ada tujuan pembelajaran.']

      ).forEach(text => {

        const li = document.createElement('li');



        li.textContent = text;

        list.appendChild(li);

      });



      const question = $('gmDetailTriggerQuestion');



      question.textContent =

        selectedChapter.pertanyaan_pemantik ||

        'Belum ada pertanyaan pemantik.';



      question.style.whiteSpace = 'pre-line';



      renderSubchapters();



      listScreen.hidden = true;

      detailScreen.hidden = false;



      window.scrollTo({

        top: 0,

        behavior: 'smooth'

      });

    } catch (e) {

      notify(e.message, true);

    }

  }



  // ========================================================

  // 07. DAFTAR DAN PREVIEW SUBBAB

  // ========================================================



  // SALUANG_GURU_MEDIA_PREVIEW_V1 — pratinjau hanya baca, tidak menyimpan data.
  function safePreviewUrl(value) {
    const raw = String(value || '').trim();
    if (!raw || /[\\\s<>"'`]/.test(raw)) return '';
    if (/^https?:\/\//i.test(raw)) return raw;
    if (/^\/\/(?:[^/]+)/.test(raw)) return ''; // hindari URL protocol-relative
    if (raw.startsWith('/')) return raw;
    if (/^storage\//i.test(raw)) return '/' + raw;
    if (/^public\/storage\//i.test(raw)) return '/' + raw.slice(7);
    if (/^(?:subchapters|subchapter-media|materials)\//i.test(raw)) return '/storage/' + raw;
    if (/^(?:uploads|media)\//i.test(raw)) return '/' + raw;
    return '';
  }

  function youtubeEmbedUrl(value) {
    try {
      const u = new URL(String(value || ''));
      const host = u.hostname.toLowerCase();
      let id = '';
      if (host === 'youtu.be' || host === 'www.youtu.be') id = u.pathname.split('/')[1];
      else if (['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'].includes(host)) {
        id = u.pathname === '/watch' ? u.searchParams.get('v') : u.pathname.match(/^\/(?:embed|shorts|live)\/([^/]+)/)?.[1];
      }
      return /^[A-Za-z0-9_-]{11}$/.test(id || '') ? 'https://www.youtube-nocookie.com/embed/' + id : '';
    } catch { return ''; }
  }

  function previewMediaHTML(block) {
    const p = block.payload || {};
    const m = block.media || {};
    const title = escapeHTML(p.title || m.title || (block.type === 'image' ? 'Gambar pembelajaran' : 'Video pembelajaran'));
    const caption = escapeHTML(p.caption || m.caption || '');
    const rawUrl = m.url || m.file_url || m.public_url || m.media_url || p.media_url || m.path || m.file_path || '';
    const url = safePreviewUrl(rawUrl);
    const youtube = block.type === 'video' ? youtubeEmbedUrl(p.youtube_url || p.url || m.youtube_url || m.url) : '';
    let visual = '';
    if (block.type === 'image' && url) {
      visual = `<img src="${escapeHTML(url)}" alt="${title}" loading="lazy" data-gm-preview-image>`;
    } else if (block.type === 'video' && youtube) {
      visual = `<iframe src="${escapeHTML(youtube)}" title="${title}" loading="lazy" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>`;
    } else if (block.type === 'video' && url) {
      visual = `<video controls preload="metadata" playsinline src="${escapeHTML(url)}"></video>`;
    } else {
      visual = '<p class="gm-preview-unavailable">Pratinjau media tidak tersedia. Periksa melalui Edit Media.</p>';
    }
    return `<figure class="gm-preview-media gm-preview-${block.type}">${visual}<figcaption><strong>${title}</strong>${caption ? `<span>${caption}</span>` : ''}</figcaption></figure>`;
  }

  // Tampilan khusus Informasi/Kesimpulan: aman untuk teks biasa/Markdown.
  // Hanya memengaruhi pratinjau Guru, tanpa mengubah isi tersimpan.
  function renderCalloutHTML(payload) {
    const title = escapeHTML(payload.title || 'Informasi / Kesimpulan');
    const raw = String(payload.content || '').replace(/\r\n?/g, '\n').trim();
    const plain = /<\/?(?:p|div|h2|h3|ol|ul|li|br|strong|b)\b/i.test(raw)
      ? htmlToEditorText(raw)
      : raw;
    const lines = plain.split('\n');
    const parts = [];
    let items = [];
    const inline = value => escapeHTML(value).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    function flushItems() {
      if (!items.length) return;
      parts.push('<div class="gm-summary-grid">' + items.join('') + '</div>');
      items = [];
    }
    for (const source of lines) {
      const line = source.trim();
      if (!line) continue;
      const num = line.match(/^([1-8])\.\s+(.+)$/);
      if (num) {
        const label = num[2].match(/^\*\*(.+?):?\*\*\s*(.*)$/);
        const heading = label ? label[1].replace(/:$/, '') : num[2].split(':')[0].trim();
        const desc = label ? label[2] : (num[2].includes(':') ? num[2].slice(num[2].indexOf(':') + 1).trim() : '');
        items.push('<article class="gm-summary-item"><span class="gm-summary-number">' + num[1] + '</span><div><h5>' + inline(heading) + '</h5>' + (desc ? '<p>' + inline(desc) + '</p>' : '') + '</div></article>');
        continue;
      }
      flushItems();
      const heading = line.match(/^#{2,3}\s+(.+)$/);
      if (heading) {
        parts.push('<h4 class="gm-summary-heading">' + inline(heading[1]) + '</h4>');
      } else if (/^(?:RANGKUMAN KARAKTERISTIK MAKHLUK HIDUP|MAKHLUK HIDUP DI SEKITAR KITA|KESIMPULAN|AYO REFLEKSI!?|AYO MENGINGAT KEMBALI!?)$/i.test(line)) {
        parts.push('<h4 class="gm-summary-heading">' + inline(line) + '</h4>');
      } else {
        parts.push('<p class="gm-summary-paragraph">' + inline(line) + '</p>');
      }
    }
    flushItems();
    return '<section class="gm-summary-card"><header class="gm-summary-header"><span class="gm-summary-symbol" aria-hidden="true">&#10003;</span><h4>' + title + '</h4></header><div class="gm-summary-body">' + (parts.join('') || '<p>Belum ada isi kesimpulan.</p>') + '</div></section>';
  }

  // Styling diinjeksikan sekali agar CSS lama SALUANG tidak perlu diganti.
  if (!document.getElementById('gmSummaryCardStyles')) {
    const style = document.createElement('style');
    style.id = 'gmSummaryCardStyles';
    style.textContent = `
      #guruMaterialPage .gm-summary-card { margin: 22px auto; width: min(100%, 980px); box-sizing: border-box; overflow: hidden; border: 1px solid #c8e3d4; border-radius: 18px; background: #f8fdf9; box-shadow: 0 8px 22px rgba(20,81,64,.06); }
      #guruMaterialPage .gm-summary-header { display: flex; align-items: center; justify-content: center; gap: 11px; padding: 18px 20px; background: #e3f4ea; border-bottom: 1px solid #cce7d9; text-align: center; }
      #guruMaterialPage .gm-summary-header h4 { margin: 0; color: #175c47; font-size: 18px; font-weight: 700; }
      #guruMaterialPage .gm-summary-symbol { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; background: #328b61; color: #fff; font-weight: bold; }
      #guruMaterialPage .gm-summary-body { padding: 24px; }
      #guruMaterialPage .gm-summary-heading { margin: 20px 0 12px; font-size: 16px; color: #135b76; font-weight: 700; text-align: center; }
      #guruMaterialPage .gm-summary-heading:first-child { margin-top: 0; }
      #guruMaterialPage .gm-summary-paragraph { margin: 0 0 14px; color: #315e68; text-align: justify; line-height: 1.85; }
      #guruMaterialPage .gm-summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; margin: 20px 0 25px; }
      #guruMaterialPage .gm-summary-item { display: flex; gap: 12px; align-items: flex-start; padding: 15px; background: #fff; border: 1px solid #d7e9e0; border-radius: 12px; min-width: 0; }
      #guruMaterialPage .gm-summary-number { flex: 0 0 29px; display: grid; place-items: center; height: 29px; border-radius: 9px; background: #e6f4eb; color: #227148; font-weight: 700; }
      #guruMaterialPage .gm-summary-item h5 { margin: 1px 0 7px; color: #135b76; font-size: 13px; font-weight: 700; }
      #guruMaterialPage .gm-summary-item p { margin: 0; color: #456d77; font-size: 12px; line-height: 1.7; text-align: justify; }
      @media (max-width: 680px) { #guruMaterialPage .gm-summary-grid { grid-template-columns: 1fr; } #guruMaterialPage .gm-summary-body { padding: 16px; } #guruMaterialPage .gm-summary-header h4 { font-size: 15px; } }
    `;
    document.head.appendChild(style);
  }

  function renderSubchapters() {

    const items =

      selectedChapter?.subchapters || [];



    subchapterList.replaceChildren();



    if (!items.length) {

      subchapterList.innerHTML = `

        <div class="gm-empty">

          Belum ada Subbab. Klik Tambah Subbab.

        </div>

      `;

      return;

    }



    items.forEach(item => {

      const card = document.createElement('article');



      card.className = 'gm-subchapter-card';



      const h = document.createElement('h3');



      h.textContent =

        `${selectedChapter.nomor_bab}.` +

        `${item.nomor_subbab} ${item.judul}`;



      const state = document.createElement('p');



      state.textContent = item.is_published

        ? 'Dipublikasikan'

        : 'Draft';



      const actions = document.createElement('div');



      actions.className = 'gm-subchapter-actions';



      const buttons = [

        [

          'view-subchapter',

          'Lihat Materi',

          'bi-eye',

          'gm-btn-outline'

        ],

        [

          'edit-subchapter',

          'Edit',

          'bi-pencil-square',

          'gm-btn-outline'

        ],

        [

          'delete-subchapter',

          'Hapus',

          'bi-trash3',

          'gm-btn-danger'

        ]

      ];



      for (const [action, label, icon, style] of buttons) {

        const b = document.createElement('button');



        b.type = 'button';

        b.className = `gm-btn ${style}`;



        b.dataset.gmAction = action;

        b.dataset.id = item.id;



        b.innerHTML = `

          <i class="bi ${icon}"></i>

          ${label}

        `;



        if (action === 'view-subchapter') {
          b.setAttribute('aria-expanded', 'false');
          // Tidak menambahkan ikon dropdown pada tombol Lihat Materi.
        }
        actions.appendChild(b);

      }



      const content = document.createElement('div');



      content.id = `gmContent-${item.id}`;

      content.className = 'gm-subchapter-content';



      content.hidden = true;



      // Ukuran pratinjau diatur pada guru-materi.css.



      // Tampilan materi memakai HTML

      // hasil normalisasi dan pembersihan.



      const blocks = Array.isArray(item.content_blocks)

        ? item.content_blocks

        : [];

      if (blocks.length) {

        content.innerHTML = blocks.map(block => {

          const payload = block.payload || {};

          if (block.type === 'text') {

            return materialHTML(payload.content || '');

          }

          if (block.type === 'callout') {

            return renderCalloutHTML(payload);

          }

          return previewMediaHTML(block);

        }).join('');

      } else {

        content.innerHTML = materialHTML(

          item.konten || 'Belum ada isi materi.'

        );

      }



      // Ganti ikon gambar rusak dengan pesan, agar pratinjau tidak menyesatkan.
      content.querySelectorAll('img[data-gm-preview-image]').forEach(img => {
        img.addEventListener('error', () => {
          const message = document.createElement('p');
          message.className = 'gm-preview-unavailable';
          message.textContent = 'Gambar belum dapat dimuat di Dashboard Guru. Periksa URL media atau akses penyimpanannya.';
          img.replaceWith(message);
        }, { once: true });
      });

      card.append(

        h,

        state,

        actions,

        content

      );



      subchapterList.appendChild(card);

    });

  }



  async function backToChapters() {

    detailScreen.hidden = true;

    listScreen.hidden = false;



    selectedChapter = null;



    await loadChapters();



    window.scrollTo({

      top: 0,

      behavior: 'smooth'

    });

  }



  // ========================================================

  // 08. TUJUAN PEMBELAJARAN

  // ========================================================



  function addObjectiveInput(value = '') {

    const row = document.createElement('div');



    row.className = 'gm-objective-row';



    const input = document.createElement('input');



    input.type = 'text';

    input.className = 'gm-objective-input';

    input.placeholder = 'Tuliskan tujuan pembelajaran';

    input.maxLength = 1000;

    input.value = value;



    const remove = document.createElement('button');



    remove.type = 'button';

    remove.className = 'gm-remove-objective';



    remove.setAttribute(

      'aria-label',

      'Hapus tujuan'

    );



    remove.innerHTML = `

      <i class="bi bi-trash3"></i>

    `;



    remove.addEventListener('click', () => {

      row.remove();

    });



    row.append(input, remove);

    objectivesList.appendChild(row);

  }



  $('gmAddObjective')?.addEventListener(

    'click',

    () => addObjectiveInput()

  );



  const objectiveValues = () =>

    [...objectivesList.querySelectorAll('.gm-objective-input')]

      .map(input => input.value.trim())

      .filter(Boolean);



  // ========================================================

  // 09. FORM TAMBAH / EDIT BAB

  // ========================================================



  function openChapterForm(chapter = null) {

    chapterForm.reset();



    $('gmChapterId').value =

      chapter?.id || '';



    $('gmChapterNumber').value =

      chapter?.nomor_bab || '';



    $('gmChapterTitle').value =

      chapter?.judul || '';



    $('gmChapterDescription').value =

      chapter?.deskripsi || '';



    $('gmChapterIntroduction').value =

      chapter?.pengantar || '';



    $('gmChapterTriggerQuestion').value =

      chapter?.pertanyaan_pemantik || '';



    $('gmChapterPublished').checked =

      Boolean(chapter?.is_published);



    $('gmChapterModalTitle').textContent =

      chapter ? 'Edit Bab' : 'Tambah Bab';



    objectivesList.replaceChildren();



    let objectives =

      chapter?.tujuan_pembelajaran || [];



    if (typeof objectives === 'string') {

      try {

        objectives = JSON.parse(objectives);

      } catch {

        objectives = [];

      }

    }



    (

      Array.isArray(objectives) && objectives.length

        ? objectives

        : ['']

    ).forEach(addObjectiveInput);



    openModal(chapterModal);

  }



  chapterForm?.addEventListener(

    'submit',

    async e => {

      e.preventDefault();



      const id = $('gmChapterId').value;

      const button = $('gmSaveChapter');



      const payload = {

        nomor_bab: Number(

          $('gmChapterNumber').value

        ),



        judul:

          $('gmChapterTitle').value.trim(),



        deskripsi:

          $('gmChapterDescription').value.trim(),



        pengantar:

          $('gmChapterIntroduction').value.trim(),



        tujuan_pembelajaran:

          objectiveValues(),



        pertanyaan_pemantik:

          $('gmChapterTriggerQuestion').value.trim(),



        is_published:

          $('gmChapterPublished').checked

      };



      button.disabled = true;



      try {

        const response = await api(

          id

            ? chapterUrl(id)

            : '/guru/chapters',

          id ? 'PUT' : 'POST',

          payload

        );



        closeModals();



        notify(

          response.message ||

          'Bab berhasil disimpan.'

        );



        await loadChapters();



        if (

          id &&

          Number(selectedChapter?.id) === Number(id)

        ) {

          await openChapter(id);

        }

      } catch (err) {

        notify(err.message, true);

      } finally {

        button.disabled = false;

      }

    }

  );



  // ========================================================

  // 10. FORM TAMBAH / EDIT SUBBAB

  // ========================================================



  function updateMediaCatalogs() {

    renderImageGallery();

    renderVideoGallery();

  }



  function renderImageGallery() {

    const gallery = $('gmImageGallery');

    if (!gallery) return;



    gallery.replaceChildren();

    $('gmImageLibrary').value = '';



    if (!imageLibrary.length) {

      const empty = document.createElement('p');

      empty.className = 'gm-media-empty';

      empty.textContent = 'Belum ada gambar di galeri SALUANG.';

      gallery.appendChild(empty);

      return;

    }



    imageLibrary.forEach((image, index) => {

      const label = document.createElement('label');

      label.className = 'gm-media-gallery-card';



      const radio = document.createElement('input');

      radio.type = 'radio';

      radio.name = 'gmImageLibraryChoice';

      radio.value = image.path;

      radio.dataset.imageUrl = image.url;



      const preview = document.createElement('img');

      preview.src = image.url;

      preview.alt = '';

      preview.loading = 'lazy';



      const name = document.createElement('span');

      name.textContent = image.name;



      label.append(radio, preview, name);

      gallery.appendChild(label);



      radio.addEventListener('change', () => {

        $('gmImageLibrary').value = radio.value;

        showImagePreview(image.url, image.name);

        updateMediaFields();

      });



      if (index === 0 && $('gmImageAction')?.value === 'library') {

        radio.checked = true;

        $('gmImageLibrary').value = image.path;

      }

    });

  }



  function renderVideoGallery() {

    const gallery = $('gmVideoGallery');

    if (!gallery) return;



    gallery.replaceChildren();

    $('gmVideoLibrary').value = '';



    if (!videoLibrary.length) {

      const empty = document.createElement('p');

      empty.className = 'gm-media-empty';

      empty.textContent = 'Belum ada video tersimpan di pustaka SALUANG.';

      gallery.appendChild(empty);

      return;

    }



    videoLibrary.forEach(video => {

      const label = document.createElement('label');

      label.className = 'gm-media-gallery-card gm-media-video-card';



      const radio = document.createElement('input');

      radio.type = 'radio';

      radio.name = 'gmVideoLibraryChoice';

      radio.value = String(video.id);



      const icon = document.createElement('span');

      icon.className = 'gm-media-video-card-icon';

      icon.setAttribute('aria-hidden', 'true');

      icon.innerHTML = '<i class="bi bi-play-circle"></i>';



      const name = document.createElement('span');

      name.textContent = video.title;



      label.append(radio, icon, name);

      gallery.appendChild(label);



      radio.addEventListener('change', () => {

        $('gmVideoLibrary').value = radio.value;

        showVideoPreview(null, video.title, 'Video tersimpan di pustaka SALUANG.');

        updateMediaFields();

      });

    });

  }



  function setMediaAction(type, action) {

    const input = $(type === 'image' ? 'gmImageAction' : 'gmVideoAction');

    const choice = document.querySelector(

      `[data-media-choice="${type}"][value="${action}"]`

    );



    if (!input || !choice || choice.disabled) return;



    input.value = action;

    choice.checked = true;

    updateMediaFields();

  }



  function showImagePreview(url, title = '') {

    const preview = $('gmImagePreview');

    const image = $('gmImagePreviewImage');



    if (!preview || !image || !url) {

      if (preview) preview.hidden = true;

      return;

    }



    image.src = url;

    image.alt = title || 'Pratinjau gambar Subbab';

    $('gmImagePreviewCaption').textContent =

      title ? `Pratinjau · ${title}` : 'Pratinjau gambar';

    preview.hidden = false;

  }



  function showVideoPreview(url, title = '', note = '') {

    const container = $('gmVideoCurrentPreview');

    if (!container) return;



    container.replaceChildren();



    if (url) {

      const video = document.createElement('video');

      video.controls = true;

      video.preload = 'metadata';

      video.playsInline = true;



      const source = document.createElement('source');

      source.src = url;

      source.type = 'video/mp4';

      video.appendChild(source);

      container.appendChild(video);

    }



    const detail = document.createElement('div');

    detail.className = 'gm-media-current-video-copy';

    const strong = document.createElement('strong');

    strong.textContent = title || 'Video aktif';

    const description = document.createElement('span');

    description.textContent = note || 'Video akan tetap digunakan pada Subbab ini.';

    detail.append(strong, description);

    container.appendChild(detail);

    container.hidden = false;

  }



  function updateMediaFields() {

    const imageAction = $('gmImageAction')?.value;

    const videoAction = $('gmVideoAction')?.value;



    document.querySelectorAll('[data-image-action]').forEach(field => {

      field.hidden = field.dataset.imageAction !== imageAction;

    });



    document.querySelectorAll('[data-video-action]').forEach(field => {

      field.hidden = field.dataset.videoAction !== videoAction;

    });



    const imageLibraryField = $('gmImageGallery');

    const videoLibraryField = $('gmVideoGallery');

    if (imageLibraryField) {

      imageLibraryField.hidden = imageAction !== 'library';

    }

    if (videoLibraryField) {

      videoLibraryField.hidden = videoAction !== 'library';

    }



    const imageFile = $('gmImageFile');

    const imageLibrary = $('gmImageLibrary');

    const videoLibrary = $('gmVideoLibrary');

    const videoFile = $('gmVideoFile');

    const youtubeUrl = $('gmYoutubeUrl');

    const imageMetadata = document.querySelector(

      '[data-media-metadata="image"]'

    );

    const videoMetadata = document.querySelector(

      '[data-media-metadata="video"]'

    );

    const imagePreview = $('gmImagePreview');

    const videoPreview = $('gmVideoCurrentPreview');



    if (imageLibrary) imageLibrary.required = false;

    if (imageFile) imageFile.required = imageAction === 'upload';

    if (videoLibrary) videoLibrary.required = false;

    if (videoFile) videoFile.required = videoAction === 'upload';

    if (youtubeUrl) youtubeUrl.required = videoAction === 'youtube';



    const imageLibraryChoiceInput = document.querySelector(

      'input[name="gmImageLibraryChoice"]'

    );

    const videoLibraryChoiceInput = document.querySelector(

      'input[name="gmVideoLibraryChoice"]'

    );

    if (imageLibraryChoiceInput) {

      imageLibraryChoiceInput.required = imageAction === 'library';

    }

    if (videoLibraryChoiceInput) {

      videoLibraryChoiceInput.required = videoAction === 'library';

    }



    document.querySelectorAll('[data-needs-image]').forEach(option => {

      const radio = option.querySelector('input[type="radio"]');

      option.hidden = !activeImage;

      if (radio) radio.disabled = !activeImage;

    });

    document.querySelectorAll('[data-needs-video]').forEach(option => {

      const radio = option.querySelector('input[type="radio"]');

      option.hidden = !activeVideo;

      if (radio) radio.disabled = !activeVideo;

    });



    const imageLibraryChoice = document.querySelector(

      '[data-image-library-choice]'

    );

    const imageLibraryRadio = imageLibraryChoice?.querySelector('input');

    if (imageLibraryChoice) {

      imageLibraryChoice.hidden = imageLibrary.length === 0;

    }

    if (imageLibraryRadio) {

      imageLibraryRadio.disabled = imageLibrary.length === 0;

    }



    const videoLibraryChoice = document.querySelector(

      '[data-video-library-choice]'

    );

    const videoLibraryRadio = videoLibraryChoice?.querySelector('input');

    if (videoLibraryChoice) {

      videoLibraryChoice.hidden = videoLibrary.length === 0;

    }

    if (videoLibraryRadio) {

      videoLibraryRadio.disabled = videoLibrary.length === 0;

    }



    if (imageMetadata) {

      imageMetadata.hidden = ['none', 'remove'].includes(imageAction);

    }

    if (videoMetadata) {

      videoMetadata.hidden = ['none', 'remove'].includes(videoAction);

    }

    if (imageAction === 'keep' && activeImage?.url) {

      showImagePreview(activeImage.url, activeImage.title || '');

    } else if (imageAction === 'library') {

      const selectedImage = document.querySelector(

        'input[name="gmImageLibraryChoice"]:checked'

      );

      if (selectedImage?.dataset.imageUrl) {

        const selected = imageLibrary.find(

          image => image.path === selectedImage.value

        );

        showImagePreview(selectedImage.dataset.imageUrl, selected?.name || '');

      } else if (imagePreview) {

        imagePreview.hidden = true;

      }

    } else if (imageAction === 'upload' && imagePreviewUrl) {

      showImagePreview(imagePreviewUrl, $('gmImageFile')?.files?.[0]?.name || '');

    } else if (imagePreview) {

      imagePreview.hidden = true;

    }



    if (videoAction === 'keep' && activeVideo) {

      if (activeVideo.source === 'youtube') {

        showVideoPreview(

          null,

          activeVideo.title || 'Video YouTube',

          'Video ini menggunakan tautan YouTube dan akan ditampilkan sebagai pemutar tertanam.'

        );

      } else if (activeVideo.url) {

        showVideoPreview(

          activeVideo.url,

          activeVideo.title || 'Video aktif'

        );

      } else {

        showVideoPreview(

          null,

          activeVideo.title || 'Video aktif',

          'Berkas video tersimpan dan akan dipertahankan.'

        );

      }

    } else if (videoAction === 'library') {

      const selectedVideo = document.querySelector(

        'input[name="gmVideoLibraryChoice"]:checked'

      );

      if (!selectedVideo && videoPreview) {

        videoPreview.hidden = true;

      }

    } else if (videoAction === 'upload' && videoPreviewUrl) {

      showVideoPreview(

        videoPreviewUrl,

        $('gmVideoFile')?.files?.[0]?.name || '',

        'Pratinjau video yang akan diunggah.'

      );

    } else if (videoPreview) {

      videoPreview.hidden = true;

    }

  }



  function openSubchapterForm(item = null) {

    if (!selectedChapter) return;

    if (

      subchapterModal

      && !subchapterModal.hidden

      && !closeModals()

    ) {

      return;

    }



    if (imagePreviewUrl) URL.revokeObjectURL(imagePreviewUrl);

    if (videoPreviewUrl) URL.revokeObjectURL(videoPreviewUrl);

    imagePreviewUrl = null;

    videoPreviewUrl = null;

    subchapterForm.reset();

    updateMediaCatalogs();



    $('gmSubchapterId').value =

      item?.id || '';



    $('gmSubchapterNumber').value =

      item?.nomor_subbab || '';



    $('gmSubchapterTitle').value =

      item?.judul || '';



    $('gmSubchapterPublished').checked =

      Boolean(item?.is_published);



    $('gmSubchapterModalTitle').textContent =

      item ? 'Edit Subbab' : 'Tambah Subbab';



    const image = item?.media?.find(media => media.media_type === 'image');

    const video = item?.media?.find(media => media.media_type === 'video');

    activeImage = image || null;

    activeVideo = video || null;

    availableBlockMedia = item?.media || [];



    setMediaAction('image', image ? 'keep' : 'none');

    $('gmImageTitle').value = image?.title || '';

    $('gmImageCaption').value = image?.caption || '';

    setMediaAction('video', video ? 'keep' : 'none');

    $('gmVideoTitle').value = video?.title || '';

    $('gmVideoCaption').value = video?.caption || '';

    $('gmYoutubeUrl').value = '';

    $('gmImageLibrary').value = '';

    $('gmVideoLibrary').value = '';

    $('gmImagePreview').hidden = true;

    $('gmVideoCurrentPreview').hidden = true;

    updateMediaFields();



    nextBlockKey = 1;

    activeBlockKey = null;

    contentBlocks = Array.isArray(item?.content_blocks)

      ? item.content_blocks.map(normalizeContentBlock)

      : [];

    if (!Array.isArray(item?.content_blocks)) {

      if (item?.konten) {

        contentBlocks.push(normalizeContentBlock({

          type: 'text',

          payload: { content: item.konten }

        }));

      }

      (item?.media || []).forEach(media => {

        contentBlocks.push(normalizeContentBlock({

          type: media.media_type,

          media_id: media.id,

          media,

          payload: {}

        }));

      });

    }



    loadEditor('');

    $('gmTextBlockEditor').hidden = true;

    $('gmCalloutEditor').hidden = true;

    $('gmBlockMediaEditor').hidden = true;

    subchapterFormDirty = false;

    renderContentBlocks();



    openModal(subchapterModal);

  }



  document.querySelectorAll('[data-media-choice]').forEach(choice => {

    choice.addEventListener('change', () => {

      if (choice.checked) {

        setMediaAction(choice.dataset.mediaChoice, choice.value);

      }

    });

  });



  $('gmImageFile')?.addEventListener('change', e => {

    if (imagePreviewUrl) URL.revokeObjectURL(imagePreviewUrl);

    const file = e.currentTarget.files?.[0];

    imagePreviewUrl = file ? URL.createObjectURL(file) : null;

    if (imagePreviewUrl) {

      showImagePreview(imagePreviewUrl, file.name);

    }

  });



  $('gmVideoFile')?.addEventListener('change', e => {

    if (videoPreviewUrl) URL.revokeObjectURL(videoPreviewUrl);

    const file = e.currentTarget.files?.[0];

    videoPreviewUrl = file ? URL.createObjectURL(file) : null;

    if (videoPreviewUrl) {

      showVideoPreview(

        videoPreviewUrl,

        file.name,

        'Pratinjau video yang akan diunggah.'

      );

    }

  });



  subchapterForm?.addEventListener(

    'submit',

    async e => {

      e.preventDefault();



      if (!selectedChapter) return;



      const id = $('gmSubchapterId').value;

      const button = $('gmSaveSubchapter');



      if (editor) {

        syncEditor();

      }



      const payload = new FormData();

      payload.set(

        'nomor_subbab',

        $('gmSubchapterNumber').value

      );

      payload.set(

        'judul',

        $('gmSubchapterTitle').value.trim()

      );

      commitActiveContentBlock();

      payload.set('content_blocks', JSON.stringify(

        contentBlocks.map((block, index) => {

          if (block.type === 'text') {

            return { type: block.type, content: block.content };

          }

          if (block.type === 'callout') {

            return {

              type: block.type,

              title: block.title,

              content: block.content

            };

          }



          const mediaBlock = {

            type: block.type,

            source: block.source,

            title: block.title,

            caption: block.caption

          };



          if (block.source === 'existing') {

            mediaBlock.media_id = Number(block.mediaId);

          } else if (block.source === 'youtube') {

            mediaBlock.youtube_url = block.youtubeUrl;

          } else if (block.file) {

            payload.append(`content_block_files[${index}]`, block.file);

          }



          return mediaBlock;

        })

      ));

      payload.set(

        'is_published',

        $('gmSubchapterPublished').checked ? '1' : '0'

      );

      payload.set('image_action', $('gmImageAction').value);

      payload.set('image_title', $('gmImageTitle').value.trim());

      payload.set('image_caption', $('gmImageCaption').value.trim());

      payload.set('video_action', $('gmVideoAction').value);

      payload.set('video_title', $('gmVideoTitle').value.trim());

      payload.set('video_caption', $('gmVideoCaption').value.trim());



      if ($('gmImageAction').value === 'library') {

        payload.set('image_library_path', $('gmImageLibrary').value);

      }



      if ($('gmImageAction').value === 'upload') {

        payload.set('image_file', $('gmImageFile').files[0]);

      }



      if ($('gmVideoAction').value === 'library') {

        payload.set('video_library_id', $('gmVideoLibrary').value);

      }



      if ($('gmVideoAction').value === 'upload') {

        payload.set('video_file', $('gmVideoFile').files[0]);

      }



      if ($('gmVideoAction').value === 'youtube') {

        payload.set('youtube_url', $('gmYoutubeUrl').value.trim());

      }



      if (id) {

        payload.set('\_method', 'PUT');

      }



      button.disabled = true;



      try {

        const response = await api(

          subchapterUrl(

            selectedChapter.id,

            id || null

          ),

          'POST',

          payload

        );



        subchapterFormDirty = false;

        closeModals();



        notify(

          response.message ||

          'Subbab berhasil disimpan.'

        );



        await openChapter(

          selectedChapter.id

        );

      } catch (err) {

        notify(err.message, true);

      } finally {

        button.disabled = false;

      }

    }

  );



  // ========================================================

  // 11. HAPUS BAB DAN SUBBAB

  // ========================================================



  function askDelete(type, id) {

    pendingDelete = { type, id };



    $('gmDeleteTitle').textContent =

      type === 'chapter'

        ? 'Hapus Bab?'

        : 'Hapus Subbab?';



    $('gmDeleteDescription').textContent =

      type === 'chapter'

        ? 'Bab dan seluruh Subbab di dalamnya akan terhapus permanen. Lanjutkan?'

        : 'Subbab dan isi materinya akan terhapus permanen. Lanjutkan?';



    openModal(deleteModal);

  }



  $('gmConfirmDelete')?.addEventListener(

    'click',

    async e => {

      if (!pendingDelete) return;



      const button = e.currentTarget;

      button.disabled = true;



      const { type, id } = pendingDelete;



      try {

        const url = type === 'chapter'

          ? chapterUrl(id)

          : subchapterUrl(

            selectedChapter.id,

            id

          );



        const result = await api(

          url,

          'DELETE'

        );



        pendingDelete = null;

        closeModals();



        notify(

          result.message ||

          'Materi berhasil dihapus.'

        );



        if (type === 'chapter') {

          await backToChapters();

        } else {

          await openChapter(

            selectedChapter.id

          );

        }

      } catch (err) {

        notify(err.message, true);

      } finally {

        button.disabled = false;

      }

    }

  );



  // ========================================================

  // 12. TOMBOL UTAMA

  // ========================================================



  $('gmAddChapter')?.addEventListener(

    'click',

    () => openChapterForm()

  );



  $('gmAddSubchapter')?.addEventListener(

    'click',

    () => openSubchapterForm()

  );



  $('gmEditChapter')?.addEventListener(

    'click',

    () => {

      if (selectedChapter) {

        openChapterForm(selectedChapter);

      }

    }

  );



  $('gmDeleteChapter')?.addEventListener(

    'click',

    () => {

      if (selectedChapter) {

        askDelete(

          'chapter',

          selectedChapter.id

        );

      }

    }

  );



  $('gmBackToChapters')?.addEventListener(

    'click',

    backToChapters

  );



  $('gmBackToHome')?.addEventListener(

    'click',

    () => {

      backToChapters();



      document.querySelector(

        '.gd-nav [data-guru-page="beranda"]'

      )?.click();

    }

  );



  // ========================================================

  // 13. TOMBOL KARTU BAB DAN SUBBAB

  // ========================================================



  page.addEventListener('click', e => {

    const button = e.target instanceof Element

      ? e.target.closest('[data-gm-action]')

      : null;



    if (!button) return;



    const action = button.dataset.gmAction;

    const id = Number(button.dataset.id);



    if (action === 'view-chapter') {

      openChapter(id);

      return;

    }



    if (action === 'edit-chapter') {

      const item = chapters.find(

        c => Number(c.id) === id

      );



      if (item) {

        openChapterForm(item);

      }

    }



    if (action === 'delete-chapter') {

      askDelete('chapter', id);

    }



    if (action === 'view-subchapter') {

      const content = $(`gmContent-${id}`);



      if (content) {

        content.hidden = !content.hidden;
        button.setAttribute('aria-expanded', String(!content.hidden));



        button.innerHTML = content.hidden

          ? '<i class="bi bi-eye"></i> Lihat Materi'

          : '<i class="bi bi-eye-slash"></i> Tutup Materi';

      }

    }



    if (action === 'edit-subchapter') {

      const item = selectedChapter

        ?.subchapters

        ?.find(

          s => Number(s.id) === id

        );



      if (item) {

        openSubchapterForm(item);

      }

    }



    if (action === 'delete-subchapter') {

      askDelete('subchapter', id);

    }

  });



  // ========================================================

  // 14. TUTUP MODAL

  // ========================================================



  page.querySelectorAll('[data-gm-close]')

    .forEach(button => {

      button.addEventListener('click', () => {

        if (closeModals()) {

          pendingDelete = null;

        }

      });

    });



  document.addEventListener('keydown', e => {

    if (e.key === 'Escape') {

      if (closeModals()) {

        pendingDelete = null;

      }

    }

  });



  // ========================================================

  // 15. MULAI

  // ========================================================



  loadChapters();

});