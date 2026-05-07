import React, { useState } from 'react';
import { useGlobals, useDeleteGlobal, useSiteHealth } from '../../hooks/useSchema';
import HealthScorecard from './HealthScorecard';
import HealthDetail from './HealthDetail';
import SchemaScore from './SchemaScore';
import RuleTemplates from './RuleTemplates';
import CustomVariables from './CustomVariables';

/**
 * BentoGrid Dashboard — Split-plane layout showing
 * Global Schemas + Site Health at a glance.
 */
export default function BentoGrid({ onEdit, onCreate, onNavigateToRules }) {
  const { data: globals = [], isLoading } = useGlobals();
  const { data: health } = useSiteHealth();
  const deleteMutation = useDeleteGlobal();
  const [expandedHealth, setExpandedHealth] = useState(null);

  const handleDelete = (id) => {
    if (window.confirm('Delete this global schema? This cannot be undone.')) {
      deleteMutation.mutate(id);
    }
  };

  if (isLoading) {
    return (
      <div className="sp-flex sp-items-center sp-justify-center sp-py-20">
        <div className="sp-h-6 sp-w-6 sp-animate-spin sp-rounded-full sp-border-2 sp-border-brand-200 sp-border-t-brand-600" />
      </div>
    );
  }

  return (
    <div className="sp-grid sp-grid-cols-1 sp-gap-6 lg:sp-grid-cols-3">
      {/* Schema Quality Score — spans full width on top */}
      <div className="lg:sp-col-span-3">
        <SchemaScore />
      </div>

      {/* Health Scorecard */}
      <div className="lg:sp-col-span-3">
        <HealthScorecard 
          health={health} 
          onEditGlobal={(schemaId) => {
            const schema = globals.find(g => g.id === schemaId);
            if (schema) onEdit(schema);
          }}
          onNavigateToRules={onNavigateToRules} 
        />
      </div>

      {/* Recommended Rules (opt-in) */}
      <div className="lg:sp-col-span-3">
        <RuleTemplates />
      </div>

      {/* Custom Variables */}
      <div className="lg:sp-col-span-3">
        <CustomVariables />
      </div>

      {/* Global Schemas Section */}
      <div className="lg:sp-col-span-3">
        <div className="sp-mb-4 sp-flex sp-items-center sp-justify-between">
          <div>
            <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">Global Schemas</h2>
            <p className="sp-text-sm sp-text-ink-3">Site-wide structured data applied to every page</p>
          </div>
          <span className="sp-rounded-full sp-bg-brand-100 sp-px-2.5 sp-py-0.5 sp-text-xs sp-font-semibold sp-text-brand-700">
            {globals.length} active
          </span>
        </div>

        {globals.length === 0 ? (
          <EmptyState onCreate={onCreate} />
        ) : (
          <div className="sp-space-y-4">
            <div className="sp-grid sp-grid-cols-1 sp-gap-4 md:sp-grid-cols-2 lg:sp-grid-cols-3">
              {globals.map((schema) => {
                const schemaHealth = health?.globals?.find((g) => g.id === schema.id)?.health;
                return (
                  <SchemaCard
                    key={schema.id}
                    schema={schema}
                    health={schemaHealth}
                    onEdit={() => onEdit(schema)}
                    onDelete={() => handleDelete(schema.id)}
                    onInspectHealth={() => setExpandedHealth(
                      expandedHealth === schema.id ? null : schema.id
                    )}
                    isHealthExpanded={expandedHealth === schema.id}
                  />
                );
              })}

              {/* Add new card */}
              <button
                onClick={() => onCreate()}
                className="sp-group sp-flex sp-min-h-[140px] sp-flex-col sp-items-center sp-justify-center sp-gap-2 sp-rounded-xl sp-border-2 sp-border-dashed sp-border-surface-3 sp-bg-white/50 sp-p-6 sp-transition-all hover:sp-border-brand-300 hover:sp-bg-brand-50/50"
              >
                <div className="sp-flex sp-h-10 sp-w-10 sp-items-center sp-justify-center sp-rounded-lg sp-bg-surface-2 sp-text-ink-3 sp-transition-colors group-hover:sp-bg-brand-100 group-hover:sp-text-brand-600">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M12 5v14" /><path d="M5 12h14" />
                  </svg>
                </div>
                <span className="sp-text-sm sp-font-medium sp-text-ink-3 group-hover:sp-text-brand-600">
                  Add Schema
                </span>
              </button>
            </div>

            {/* Expanded health detail panel */}
            {expandedHealth && (
              <div className="sp-animate-slide-up">
                {(() => {
                  const hd = health?.globals?.find((g) => g.id === expandedHealth);
                  return hd ? <HealthDetail health={hd.health} type={hd.type} /> : null;
                })()}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

/**
 * Individual schema card in the Bento grid.
 */
function SchemaCard({ schema, health, onEdit, onDelete, onInspectHealth, isHealthExpanded }) {
  const type = schema.schema_type || 'Unknown';
  const data = schema.schema_data || {};
  const isHealthy = health?.valid !== false;
  const errorCount = health?.errors?.length || 0;
  const warningCount = health?.warnings?.length || 0;
  const hasIssues = errorCount > 0 || warningCount > 0;

  const statusColor = !health
    ? 'sp-bg-surface-3 sp-text-ink-3'
    : isHealthy && warningCount === 0
      ? 'sp-bg-green-100 sp-text-green-700'
      : isHealthy
        ? 'sp-bg-yellow-100 sp-text-yellow-700'
        : 'sp-bg-red-100 sp-text-red-700';

  const statusLabel = !health
    ? 'Unchecked'
    : isHealthy && warningCount === 0
      ? 'Valid'
      : isHealthy
        ? `${warningCount} warning${warningCount > 1 ? 's' : ''}`
        : `${errorCount} error${errorCount > 1 ? 's' : ''}`;

  return (
    <div className={`sp-group sp-relative sp-flex sp-flex-col sp-rounded-xl sp-border sp-bg-white sp-p-5 sp-shadow-bento sp-transition-all hover:sp-shadow-bento-hover ${
      isHealthExpanded ? 'sp-border-brand-300 sp-ring-1 sp-ring-brand-200' : 'sp-border-surface-3'
    }`}>
      {/* Type badge + status */}
      <div className="sp-mb-3 sp-flex sp-items-start sp-justify-between">
        <div className="sp-flex sp-items-center sp-gap-2">
          <TypeIcon type={type} />
          <span className="sp-text-sm sp-font-semibold sp-text-ink-0">{type}</span>
        </div>
        <button
          onClick={hasIssues ? onInspectHealth : undefined}
          className={`sp-rounded-full sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-transition-colors ${statusColor} ${
            hasIssues ? 'sp-cursor-pointer hover:sp-opacity-80' : ''
          }`}
          title={hasIssues ? 'Click to inspect health details' : 'Schema is valid'}
        >
          {statusLabel}
        </button>
      </div>

      {/* Preview data */}
      <div className="sp-mb-4 sp-flex-1 sp-space-y-1">
        {data.name && (
          <p className="sp-text-sm sp-text-ink-1 sp-truncate" title={data.name}>
            {data.name}
          </p>
        )}
        {data.url && (
          <p className="sp-text-xs sp-text-ink-3 sp-truncate" title={data.url}>
            {data.url}
          </p>
        )}
        {!data.name && !data.url && (
          <p className="sp-text-sm sp-italic sp-text-ink-4">No preview data</p>
        )}
      </div>

      {/* Actions */}
      <div className="sp-flex sp-items-center sp-gap-2 sp-border-t sp-border-surface-2 sp-pt-3">
        <button
          onClick={onEdit}
          className="sp-flex-1 sp-rounded-lg sp-bg-surface-1 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-1 sp-transition-colors hover:sp-bg-surface-2"
        >
          Edit
        </button>
        {hasIssues && (
          <button
            onClick={onInspectHealth}
            className="sp-rounded-lg sp-px-2.5 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-4 sp-transition-colors hover:sp-bg-yellow-50 hover:sp-text-yellow-600"
            title="Inspect health"
          >
            🔍
          </button>
        )}
        <button
          onClick={onDelete}
          className="sp-rounded-lg sp-px-2.5 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-4 sp-transition-colors hover:sp-bg-red-50 hover:sp-text-red-600"
          title="Delete"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M3 6h18" /><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" /><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
          </svg>
        </button>
      </div>

      {/* Status indicator line */}
      <div className={`sp-absolute sp-bottom-0 sp-left-4 sp-right-4 sp-h-0.5 sp-rounded-full ${
        !health ? 'sp-bg-surface-3' : isHealthy ? 'sp-bg-green-400' : 'sp-bg-red-400'
      }`} />
    </div>
  );
}

function TypeIcon({ type }) {
  const icons = {
    Organization: '🏢',
    WebSite: '🌐',
    LocalBusiness: '📍',
    Article: '📄',
    BlogPosting: '✍️',
    Product: '📦',
    FAQPage: '❓',
    Event: '📅',
    Person: '👤',
    Service: '⚙️',
    VideoObject: '🎬',
    Course: '🎓',
    Recipe: '🍳',
    JobPosting: '💼',
  };

  return (
    <span className="sp-text-lg" role="img" aria-label={type}>
      {icons[type] || '📋'}
    </span>
  );
}

function EmptyState({ onCreate }) {
  return (
    <div className="sp-flex sp-flex-col sp-items-center sp-justify-center sp-rounded-xl sp-border sp-border-dashed sp-border-surface-3 sp-bg-white/50 sp-px-8 sp-py-16">
      <div className="sp-mb-4 sp-text-4xl">🏗️</div>
      <h3 className="sp-mb-1 sp-text-base sp-font-semibold sp-text-ink-0">No schemas yet</h3>
      <p className="sp-mb-6 sp-max-w-sm sp-text-center sp-text-sm sp-text-ink-3">
        Start by creating your Organization or WebSite schema. These are the foundation of your structured data.
      </p>
      <div className="sp-flex sp-gap-3">
        <button
          onClick={() => onCreate('Organization')}
          className="sp-rounded-lg sp-bg-brand-600 sp-px-4 sp-py-2 sp-text-sm sp-font-medium sp-text-white sp-transition-colors hover:sp-bg-brand-700"
        >
          🏢 Organization
        </button>
        <button
          onClick={() => onCreate('WebSite')}
          className="sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-px-4 sp-py-2 sp-text-sm sp-font-medium sp-text-ink-1 sp-transition-colors hover:sp-bg-surface-1"
        >
          🌐 WebSite
        </button>
      </div>
    </div>
  );
}
