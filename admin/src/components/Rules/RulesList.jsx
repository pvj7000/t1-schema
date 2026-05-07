import React from 'react';
import { useRules, useDeleteRule } from '../../hooks/useSchema';

/**
 * RulesList — displays all schema rules with condition badges and actions.
 */
export default function RulesList({ onEdit, onCreateNew }) {
  const { data: rules = [], isLoading } = useRules();
  const deleteMutation = useDeleteRule();

  const handleDelete = async (id, name) => {
    if (!confirm(`Delete rule "${name}"?`)) return;
    await deleteMutation.mutateAsync(id);
  };

  if (isLoading) {
    return (
      <div className="sp-flex sp-items-center sp-justify-center sp-py-16">
        <div className="sp-h-6 sp-w-6 sp-animate-spin sp-rounded-full sp-border-2 sp-border-brand-200 sp-border-t-brand-600" />
      </div>
    );
  }

  return (
    <div className="sp-space-y-4">
      {/* Header */}
      <div className="sp-flex sp-items-center sp-justify-between">
        <div>
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">Schema Rules</h2>
          <p className="sp-text-sm sp-text-ink-3">
            Conditional schemas that apply to specific page types, archives, and taxonomies
          </p>
        </div>
        <button
          onClick={onCreateNew}
          className="sp-rounded-lg sp-bg-brand-600 sp-px-4 sp-py-2 sp-text-sm sp-font-medium sp-text-white sp-shadow-sm sp-transition-all hover:sp-bg-brand-700 hover:sp-shadow-md"
        >
          + New Rule
        </button>
      </div>

      {/* Rules list */}
      {rules.length === 0 ? (
        <div className="sp-flex sp-flex-col sp-items-center sp-justify-center sp-rounded-xl sp-border sp-border-dashed sp-border-surface-3 sp-bg-white/50 sp-py-16">
          <span className="sp-mb-2 sp-text-3xl">🎯</span>
          <p className="sp-text-sm sp-font-medium sp-text-ink-2">No schema rules yet</p>
          <p className="sp-text-xs sp-text-ink-4 sp-mt-1">Rules let you target archives, CPTs, taxonomies, and more</p>
          <button
            onClick={onCreateNew}
            className="sp-mt-4 sp-rounded-lg sp-bg-brand-600 sp-px-4 sp-py-2 sp-text-sm sp-font-medium sp-text-white sp-transition-all hover:sp-bg-brand-700"
          >
            Create Your First Rule
          </button>
        </div>
      ) : (
        <div className="sp-space-y-2">
          {rules.map((rule) => (
            <RuleRow key={rule.id} rule={rule} onEdit={() => onEdit(rule)} onDelete={() => handleDelete(rule.id, rule.rule_name)} />
          ))}
        </div>
      )}
    </div>
  );
}

function RuleRow({ rule, onEdit, onDelete }) {
  const conditions = rule.conditions || [];
  const statusColor = rule.status === 'active'
    ? 'sp-bg-green-100 sp-text-green-700'
    : 'sp-bg-surface-2 sp-text-ink-3';

  return (
    <div className="sp-group sp-flex sp-items-center sp-justify-between sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-px-5 sp-py-4 sp-shadow-bento sp-transition-all hover:sp-shadow-bento-hover">
      <div className="sp-flex sp-flex-1 sp-items-center sp-gap-4">
        {/* Schema type badge */}
        <span className="sp-rounded sp-bg-brand-100 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-brand-700">
          {rule.schema_type}
        </span>

        {/* Rule name */}
        <div className="sp-min-w-0 sp-flex-1">
          <p className="sp-text-sm sp-font-medium sp-text-ink-0 sp-truncate">{rule.rule_name}</p>
          <div className="sp-flex sp-flex-wrap sp-gap-1 sp-mt-1">
            {conditions.map((c, i) => (
              <span key={i} className="sp-rounded sp-bg-surface-2 sp-px-1.5 sp-py-0.5 sp-text-2xs sp-text-ink-2">
                {c.type}{c.value ? `:${c.value}` : ''}
              </span>
            ))}
          </div>
        </div>

        {/* Priority + Status */}
        <div className="sp-flex sp-items-center sp-gap-2">
          <span className="sp-text-2xs sp-text-ink-4">P{rule.priority}</span>
          <span className={`sp-rounded-full sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold ${statusColor}`}>
            {rule.status}
          </span>
        </div>
      </div>

      {/* Actions */}
      <div className="sp-ml-4 sp-flex sp-items-center sp-gap-1 sp-opacity-0 sp-transition-opacity group-hover:sp-opacity-100">
        <button
          onClick={onEdit}
          className="sp-rounded-lg sp-border sp-border-surface-3 sp-bg-surface-1 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-1 sp-transition-colors hover:sp-bg-brand-50 hover:sp-text-brand-600 hover:sp-border-brand-200"
        >
          Edit
        </button>
        <button
          onClick={onDelete}
          className="sp-rounded-lg sp-px-2 sp-py-1.5 sp-text-xs sp-text-ink-4 sp-transition-colors hover:sp-bg-red-50 hover:sp-text-red-600"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M3 6h18" /><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" /><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
          </svg>
        </button>
      </div>
    </div>
  );
}
