import React, { useState, useEffect, useMemo } from 'react';
import { useSchemaTypes, useCreateGlobal, useUpdateGlobal, useCustomVariables } from '../../hooks/useSchema';
import ObjectEditor from './ObjectEditor';
import TypeSelector from './TypeSelector';
import JsonImporter from './JsonImporter';
import VariablePicker from './VariablePicker';
import RichSnippet from '../Preview/RichSnippet';
import HealthDetail from '../Dashboard/HealthDetail';

/**
 * Resolve {{custom.*}} variable tags in a schema object for preview display.
 */
function resolveCustomVariables(schema, customVars) {
  if (!customVars || typeof customVars !== 'object') return JSON.stringify(schema, null, 2);
  const json = JSON.stringify(schema, null, 2);
  return json.replace(/\{\{custom\.([a-z0-9_]+)\}\}/g, (match, key) => {
    return key in customVars ? customVars[key] : match;
  });
}

/**
 * SchemaBuilder — Recursive visual schema editor.
 *
 * Converts JSON schema into interactive property cards.
 */
export default function SchemaBuilder({ schema, onBack }) {
  const { data: typeDefs = {} } = useSchemaTypes();
  const { data: customVars = {} } = useCustomVariables();
  const createMutation = useCreateGlobal();
  const updateMutation = useUpdateGlobal();

  const isNew = schema?.isNew;

  // schemaType can be a string ("Organization") or array (["Organization", "ProfessionalService"])
  const initType = isNew
    ? (schema?.preselectedType || '')
    : (schema?.schema_data?.['@type'] || schema?.schema_type || '');
  const [schemaType, setSchemaType] = useState(initType);
  const [schemaData, setSchemaData] = useState(
    isNew ? {} : (schema?.schema_data || {})
  );
  const [showImporter, setShowImporter] = useState(false);
  const [showPreview, setShowPreview] = useState(false);
  const [activeVariableField, setActiveVariableField] = useState(null);
  const [saving, setSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);

  // Derive the primary type for registry lookups (first element if array).
  const primaryType = Array.isArray(schemaType) ? schemaType[0] || '' : schemaType || '';

  // When type changes, ensure @context and @type are set.
  useEffect(() => {
    if (schemaType) {
      setSchemaData((prev) => ({
        ...prev,
        '@context': 'https://schema.org',
        '@type': schemaType,
      }));
    }
  }, [schemaType]);

  const typeDef = typeDefs[primaryType] || null;
  const properties = typeDef?.properties || {};

  const handleDataChange = (newData) => {
    setSchemaData({ ...newData, '@context': 'https://schema.org', '@type': schemaType });
  };

  const handleImport = (imported) => {
    if (imported['@type']) {
      // Preserve array or string @type from import.
      setSchemaType(imported['@type']);
    }
    setSchemaData(imported);
    setShowImporter(false);
  };

  const handleInsertVariable = (variable) => {
    if (activeVariableField) {
      handleDataChange({ ...schemaData, [activeVariableField]: `{{${variable}}}` });
      setActiveVariableField(null);
    }
  };

  const handleSave = async () => {
    setSaving(true);
    setSaveSuccess(false);
    try {
      if (isNew) {
        await createMutation.mutateAsync({
          schema_type: primaryType,
          schema_data: schemaData,
          status: 'active',
        });
      } else {
        await updateMutation.mutateAsync({
          id: schema.id,
          schema_type: primaryType,
          schema_data: schemaData,
        });
      }
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 2000);
    } catch (err) {
      alert('Save failed: ' + err.message);
    } finally {
      setSaving(false);
    }
  };

  // Client-side validation.
  const liveHealth = useMemo(() => {
    if (!schemaType || !typeDef) return null;
    const errors = [];
    const warnings = [];
    for (const [key, def] of Object.entries(properties)) {
      const val = schemaData[key];
      const empty = val === undefined || val === '' || val === null;
      const typeLabel = Array.isArray(schemaType) ? schemaType.join(' + ') : schemaType;
      if (def.required && empty) errors.push(`Missing required property: '${key}' for type '${typeLabel}'.`);
      else if (def.recommended && empty) warnings.push(`Missing recommended property: '${key}' for type '${typeLabel}'.`);
    }
    return { valid: errors.length === 0, errors, warnings };
  }, [schemaData, schemaType, typeDef, properties]);

  return (
    <div className="sp-grid sp-grid-cols-1 sp-gap-6 lg:sp-grid-cols-3">
      {/* Editor Panel */}
      <div className="lg:sp-col-span-2">
        <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
          {/* Editor Header */}
          <div className="sp-flex sp-items-center sp-justify-between sp-border-b sp-border-surface-2 sp-px-6 sp-py-4">
            <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">
              {isNew ? 'New Schema' : `Edit: ${primaryType}${Array.isArray(schemaType) && schemaType.length > 1 ? ` + ${schemaType.length - 1} more` : ''}`}
            </h2>
            <div className="sp-flex sp-items-center sp-gap-2">
              <button
                onClick={() => setShowImporter(!showImporter)}
                className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-1"
              >
                {showImporter ? 'Visual Editor' : '{ } Import JSON'}
              </button>
              <button
                onClick={() => setShowPreview(!showPreview)}
                className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-1 lg:sp-hidden"
              >
                👁 Preview
              </button>
            </div>
          </div>

          <div className="sp-p-6">
            {showImporter ? (
              <JsonImporter onImport={handleImport} />
            ) : (
              <>
                {/* Type Selector */}
                <div className="sp-mb-6">
                  <TypeSelector
                    value={schemaType}
                    onChange={setSchemaType}
                    types={typeDefs}
                  />
                </div>

                {/* Properties */}
                {primaryType && (
                  <div className="sp-space-y-3">
                    <div className="sp-mb-3 sp-flex sp-items-center sp-justify-between">
                      <h3 className="sp-text-sm sp-font-semibold sp-text-ink-1">Properties</h3>
                      {typeDef && (
                        <span className="sp-text-2xs sp-text-ink-3">
                          {Object.keys(properties).length} available
                        </span>
                      )}
                    </div>

                    <ObjectEditor
                      type={primaryType}
                      data={schemaData}
                      onChange={handleDataChange}
                      typeDefs={typeDefs}
                      activeVariableField={activeVariableField}
                      setActiveVariableField={setActiveVariableField}
                    />
                  </div>
                )}

                {!primaryType && (
                  <div className="sp-flex sp-flex-col sp-items-center sp-justify-center sp-py-12 sp-text-center">
                    <span className="sp-mb-3 sp-text-3xl">🎯</span>
                    <p className="sp-text-sm sp-text-ink-3">
                      Select a Schema type above to start building
                    </p>
                  </div>
                )}
              </>
            )}
          </div>

          {/* Save Bar */}
          {primaryType && (
            <div className="sp-flex sp-items-center sp-justify-between sp-border-t sp-border-surface-2 sp-bg-surface-1 sp-px-6 sp-py-3">
              <div className="sp-text-xs sp-text-ink-3">
                {Object.keys(schemaData).filter((k) => !k.startsWith('@')).length} properties set
              </div>
              <div className="sp-flex sp-items-center sp-gap-2">
                {saveSuccess && (
                  <span className="sp-animate-fade-in sp-text-xs sp-font-medium sp-text-green-600">
                    ✓ Saved
                  </span>
                )}
                <button
                  onClick={handleSave}
                  disabled={saving || !primaryType}
                  className="sp-rounded-lg sp-bg-brand-600 sp-px-4 sp-py-1.5 sp-text-sm sp-font-medium sp-text-white sp-transition-all hover:sp-bg-brand-700 disabled:sp-opacity-50"
                >
                  {saving ? 'Saving…' : isNew ? 'Create Schema' : 'Save Changes'}
                </button>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Sidebar: Preview + Variable Picker */}
      <div className="sp-space-y-4">
        {/* Variable Picker */}
        {activeVariableField && (
          <VariablePicker
            onSelect={handleInsertVariable}
            onClose={() => setActiveVariableField(null)}
            targetField={activeVariableField}
          />
        )}

        {/* Live Health Validation */}
        {liveHealth && <HealthDetail health={liveHealth} type={primaryType} />}

        {/* Rich Snippet Preview */}
        <RichSnippet schemaData={schemaData} />

        {/* Raw JSON Preview */}
        <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
          <div className="sp-border-b sp-border-surface-2 sp-px-4 sp-py-3">
            <h3 className="sp-text-xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
              JSON-LD Output
            </h3>
          </div>
          <pre className="sp-max-h-64 sp-overflow-auto sp-p-4 sp-font-mono sp-text-xs sp-text-ink-2">
            {resolveCustomVariables(schemaData, customVars)}
          </pre>
        </div>
      </div>
    </div>
  );
}
