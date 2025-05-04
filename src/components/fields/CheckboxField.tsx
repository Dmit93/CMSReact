import React from 'react';

interface Option {
  value: string;
  label: string;
}

interface CheckboxFieldProps {
  id: string;
  name: string;
  label?: string;
  options: Option[];
  value: string[];
  onChange: (name: string, value: string[]) => void;
  disabled?: boolean;
  required?: boolean;
  error?: string;
  description?: string;
  className?: string;
  inline?: boolean;
}

const CheckboxField: React.FC<CheckboxFieldProps> = ({
  id,
  name,
  label,
  options,
  value,
  onChange,
  disabled = false,
  required = false,
  error,
  description,
  className = '',
  inline = false
}) => {
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const optionValue = e.target.value;
    const isChecked = e.target.checked;
    
    let newValues: string[];
    
    if (isChecked) {
      // Добавляем значение, если его еще нет
      newValues = value.includes(optionValue) ? value : [...value, optionValue];
    } else {
      // Удаляем значение
      newValues = value.filter(val => val !== optionValue);
    }
    
    onChange(name, newValues);
  };

  return (
    <div className={`mb-4 ${className}`}>
      {label && (
        <div className="text-sm font-medium text-gray-700 mb-2">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </div>
      )}
      
      <div className={inline ? 'flex flex-wrap gap-4' : 'space-y-2'}>
        {options.map((option) => {
          const optionId = `${id}-${option.value}`;
          const isChecked = value.includes(option.value);
          
          return (
            <div key={option.value} className={inline ? 'flex items-center' : 'flex items-start'}>
              <div className="flex items-center h-5">
                <input
                  id={optionId}
                  type="checkbox"
                  name={name}
                  value={option.value}
                  checked={isChecked}
                  onChange={handleChange}
                  disabled={disabled}
                  className={`h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 ${
                    disabled ? 'opacity-50 cursor-not-allowed' : ''
                  }`}
                />
              </div>
              <div className="ml-2 text-sm">
                <label htmlFor={optionId} className={`font-medium text-gray-700 ${
                  disabled ? 'opacity-50 cursor-not-allowed' : ''
                }`}>
                  {option.label}
                </label>
              </div>
            </div>
          );
        })}
      </div>
      
      {error && <p className="mt-1 text-sm text-red-500">{error}</p>}
      {description && <p className="mt-1 text-xs text-gray-500">{description}</p>}
    </div>
  );
};

export default CheckboxField; 