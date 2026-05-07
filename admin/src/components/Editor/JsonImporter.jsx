import React, { useState } from 'react';

/**
 * JsonImporter — paste raw JSON-LD and import into the visual editor.
 */
export default function JsonImporter({ onImport }) {
  const [raw, setRaw] = useState('');
  const [error, setError] = useState(null);

  const handleImport = () => {
    setError(null);
    try {
      const parsed = JSON.parse(raw);

      // Handle @graph arrays: import the first item.
      let schema = parsed;
      if (parsed['@graph'] && Array.isArray(parsed['@graph'])) {
        schema = parsed['@graph'][0] || {};
      }

      if (!schema['@type']) {
        setError('JSON must contain an @type property.');
        return;
      }

      onImport(schema);
    } catch (e) {
      setError(`Invalid JSON: ${e.message}`);
    }
  };

  const handlePaste = (e) => {
    // Auto-detect and clean pasted content.
    const pasted = e.clipboardData?.getData('text') || '';
    if (pasted.trim().startsWith('<script')) {
      // Strip script tags if user pasted raw HTML.
      const cleaned = pasted.replace(/<\/?script[^>]*>/gi, '').trim();
      setRaw(cleaned);
      e.preventDefault();
    }
  };

  return (
    <div className="sp-space-y-4">
      <div>
        <label className="sp-mb-1.5 sp-block sp-text-sm sp-font-medium sp-text-ink-1">
          Paste JSON-LD
        </label>
        <p className="sp-mb-3 sp-text-xs sp-text-ink-3">
          Paste raw JSON-LD markup. Script tags will be stripped automatically.
        </p>
        <textarea
          value={raw}
          onChange={(e) => { setRaw(e.target.value); setError(null); }}
          onPaste={handlePaste}
          placeholder={'{\n  "@context": "https://schema.org",\n  "@type": "Organization",\n  "name": "Your Company"\n}'}
          rows={12}
          className="sp-w-full sp-rounded-lg sp-border sp-border-surface-3 sp-bg-surface-1 sp-p-4 sp-font-mono sp-text-sm sp-text-ink-1 sp-outline-none sp-transition-colors focus:sp-border-brand-400 focus:sp-ring-1 focus:sp-ring-brand-200"
          spellCheck="false"
        />
      </div>

      {error && (
        <div className="sp-rounded-lg sp-border sp-border-red-200 sp-bg-red-50 sp-px-4 sp-py-2 sp-text-sm sp-text-red-600">
          {error}
        </div>
      )}

      <div className="sp-flex sp-justify-end sp-gap-2">
        <button
          onClick={() => { setRaw(''); setError(null); }}
          className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-4 sp-py-2 sp-text-sm sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-1"
        >
          Clear
        </button>
        <button
          onClick={handleImport}
          disabled={!raw.trim()}
          className="sp-rounded-lg sp-bg-brand-600 sp-px-4 sp-py-2 sp-text-sm sp-font-medium sp-text-white sp-transition-colors hover:sp-bg-brand-700 disabled:sp-opacity-50"
        >
          Import & Populate
        </button>
      </div>
    </div>
  );
}
