import TextField from './TextField';
import TextareaField from './TextareaField';
import SelectField from './SelectField';
import DateField from './DateField';
import CheckboxField from './CheckboxField';

// Фабрика компонентов для получения нужного компонента по типу поля
export const getFieldComponent = (fieldType: string) => {
  switch (fieldType) {
    case 'text':
      return TextField;
    case 'textarea':
      return TextareaField;
    case 'select':
      return SelectField;
    case 'checkbox':
      return CheckboxField;
    case 'date':
      return DateField;
    // В будущем можно добавить другие типы полей
    default:
      return TextField; // По умолчанию возвращаем текстовое поле
  }
};

export {
  TextField,
  TextareaField,
  SelectField,
  DateField,
  CheckboxField
}; 