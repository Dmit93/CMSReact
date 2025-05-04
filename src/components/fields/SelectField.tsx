import React from 'react';

interface Option {
  value: string;
  label: string;
}

interface SelectFieldProps {
  id: string;
  name: string;
  label?: string;
  placeholder?: string;
  value: string;
  options: Option[];
  onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
  disabled?: boolean;
  required?: boolean;
  error?: string;
  description?: string;
  className?: string;
  emptyOption?: boolean;
  emptyOptionLabel?: string;
}

const SelectField: React.FC<SelectFieldProps> = ({
  id,
  name,
  label,
  placeholder,
  value,
  options,
  onChange,
  disabled = false,
  required = false,
  error,
  description,
  className = '',
  emptyOption = false,
  emptyOptionLabel = 'Выберите...'
}) => {
  return (
    <div className={`mb-4 ${className}`}>
      {label && (
        <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-1">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </label>
      )}
      <select
        id={id}
        name={name}
        value={value}
        onChange={onChange}
        disabled={disabled}
        required={required}
        className={`w-full px-3 py-2 border ${
          error ? 'border-red-500' : 'border-gray-300'
        } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
          disabled ? 'bg-gray-100 text-gray-500' : ''
        } appearance-none bg-white`}
      >
        {emptyOption && (
          <option value="" disabled={required}>
            {placeholder || emptyOptionLabel}
          </option>
        )}
        
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      
      {error && <p className="mt-1 text-sm text-red-500">{error}</p>}
      {description && <p className="mt-1 text-xs text-gray-500">{description}</p>}
    </div>
  );
};

export default SelectField; 