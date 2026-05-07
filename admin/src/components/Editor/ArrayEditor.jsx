import React from 'react';
import ObjectEditor from './ObjectEditor';

const PRIMITIVE_TYPES = ['Text', 'URL', 'Date', 'DateTime', 'Time', 'Number', 'Integer', 'Float', 'Boolean'];

/**
 * ArrayEditor — Renders a list of items for array types.
 * Handles both complex objects (e.g. Question[]) and primitives (e.g. URL[]).
 */
export default function ArrayEditor({
  itemType,
  items = [],
  onChange,
  typeDefs,
  activeVariableField,
  setActiveVariableField,
}) {
  const isPrimitive = PRIMITIVE_TYPES.includes(itemType);

  const handleAddItem = () => {
    onChange([...items, isPrimitive ? '' : { '@type': itemType }]);
  };

  const handleUpdateItem = (index, newData) => {
    const nextItems = [...items];
    nextItems[index] = newData;
    onChange(nextItems);
  };

  const handleRemoveItem = (index) => {
    const nextItems = [...items];
    nextItems.splice(index, 1);
    onChange(nextItems);
  };

  return (
    <div className="sp-space-y-4">
      {items.map((item, index) => (
        <div key={index} className="sp-relative sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-p-4 sp-shadow-sm">
          <div className="sp-mb-3 sp-flex sp-items-center sp-justify-between">
            <span className="sp-text-xs sp-font-bold sp-uppercase sp-tracking-wider sp-text-ink-3">
              Item {index + 1}
            </span>
            <button
              onClick={() => handleRemoveItem(index)}
              className="sp-text-xs sp-font-medium sp-text-red-500 hover:sp-text-red-700"
            >
              Remove
            </button>
          </div>

          {isPrimitive ? (
            <input
              type="text"
              value={typeof item === 'string' ? item : ''}
              onChange={(e) => handleUpdateItem(index, e.target.value)}
              placeholder={itemType === 'URL' ? 'https://…' : `Enter ${itemType}`}
              className="sp-w-full sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-px-3 sp-py-2 sp-text-sm sp-text-ink-0 sp-outline-none sp-transition-colors focus:sp-border-brand-400 focus:sp-ring-1 focus:sp-ring-brand-200"
            />
          ) : (
            <ObjectEditor
              type={itemType}
              data={item}
              onChange={(newData) => handleUpdateItem(index, newData)}
              typeDefs={typeDefs}
              activeVariableField={activeVariableField}
              setActiveVariableField={setActiveVariableField}
            />
          )}
        </div>
      ))}

      <button
        onClick={handleAddItem}
        className="sp-flex sp-w-full sp-items-center sp-justify-center sp-gap-2 sp-rounded-lg sp-border-2 sp-border-dashed sp-border-surface-3 sp-bg-surface-1 sp-py-3 sp-text-sm sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-border-brand-300 hover:sp-bg-brand-50 hover:sp-text-brand-600"
      >
        <span>+ Add {itemType}</span>
      </button>
    </div>
  );
}
