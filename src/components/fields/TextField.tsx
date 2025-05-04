import React from 'react';

interface TextFieldProps {
  id: string;
  name: string;
  label?: string;
  placeholder?: string;
  value: string;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  disabled?: boolean;
  required?: boolean;
  error?: string;
  description?: string;
  className?: string;
  type?: string;
  min?: string | number;
  max?: string | number;
  pattern?: string;
  step?: string | number;
}

const TextField: React.FC<TextFieldProps> = ({
  id,
  name,
  label,
  placeholder,
  value,
  onChange,
  disabled = false,
  required = false,
  error,
  description,
  className = '',
  type = 'text',
  min,
  max,
  pattern,
  step
}) => {
  return (
    <div className={`mb-4 ${className}`}>
      {label && (
        <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-1">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </label>
      )}
      <input
        id={id}
        type={type}
        name={name}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        disabled={disabled}
        required={required}
        min={min}
        max={max}
        pattern={pattern}
        step={step}
        className={`w-full px-3 py-2 border ${
          error ? 'border-red-500' : 'border-gray-300'
        } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
          disabled ? 'bg-gray-100 text-gray-500' : ''
        }`}
      />
      {error && <p className="mt-1 text-sm text-red-500">{error}</p>}
      {description && <p className="mt-1 text-xs text-gray-500">{description}</p>}
    </div>
  );
};

export { type TextFieldProps };
export default TextField; 