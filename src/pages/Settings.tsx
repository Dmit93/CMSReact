import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import { Button } from '../components/ui/button';
import { Loader2, Save, AlertCircle } from 'lucide-react';
import apiClient from '../services/api';

interface Setting {
  name: string;
  value: string;
  description?: string;
  isEditing?: boolean;
}

const Settings: React.FC = () => {
  const [settings, setSettings] = useState<Setting[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [saving, setSaving] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Загрузка настроек
  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await apiClient.get('/settings');
      
      if (response && response.data && response.data.success) {
        // Преобразуем объект настроек в массив объектов для удобства отображения
        const settingsArray = Object.entries(response.data.data).map(([name, value]) => ({
          name,
          value: value as string,
          isEditing: false
        }));
        
        setSettings(settingsArray);
      } else {
        setError((response?.data?.message) || 'Не удалось загрузить настройки');
      }
    } catch (err) {
      console.error(err);
      setError('Ошибка при загрузке настроек');
    } finally {
      setLoading(false);
    }
  };

  // Обновление значения настройки в состоянии
  const handleSettingChange = (index: number, value: string) => {
    const updatedSettings = [...settings];
    updatedSettings[index] = {
      ...updatedSettings[index],
      value
    };
    setSettings(updatedSettings);
  };

  // Переключение режима редактирования для настройки
  const toggleEditMode = (index: number) => {
    const updatedSettings = [...settings];
    updatedSettings[index] = {
      ...updatedSettings[index],
      isEditing: !updatedSettings[index].isEditing
    };
    setSettings(updatedSettings);
  };

  // Сохранение всех настроек
  const saveSettings = async () => {
    try {
      setSaving(true);
      setError(null);
      setSuccess(null);
      
      // Преобразуем массив настроек обратно в объект
      const settingsObject: Record<string, string> = {};
      settings.forEach(setting => {
        settingsObject[setting.name] = setting.value;
      });
      
      const response = await apiClient.put('/settings', settingsObject);
      
      if (response && response.data && response.data.success) {
        setSuccess('Настройки успешно сохранены');
        
        // Отключаем режим редактирования для всех настроек
        const updatedSettings = settings.map(setting => ({
          ...setting,
          isEditing: false
        }));
        setSettings(updatedSettings);
      } else {
        setError((response?.data?.message) || 'Не удалось сохранить настройки');
      }
    } catch (err) {
      console.error(err);
      setError('Ошибка при сохранении настроек');
    } finally {
      setSaving(false);
    }
  };

  // Функция для отображения понятного названия настройки
  const getSettingLabel = (name: string): string => {
    const labels: Record<string, string> = {
      'site_title': 'Название сайта',
      'site_description': 'Описание сайта',
      'active_theme': 'Активная тема',
      'posts_per_page': 'Записей на страницу',
      'allow_comments': 'Разрешить комментарии',
      'timezone': 'Часовой пояс'
    };
    
    return labels[name] || name;
  };

  return (
    <Card className="w-full">
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle>Настройки сайта</CardTitle>
        <Button 
          variant="default" 
          size="sm" 
          onClick={saveSettings}
          disabled={saving || loading}
          className="flex items-center gap-2"
        >
          {saving ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              Сохранение...
            </>
          ) : (
            <>
              <Save className="h-4 w-4" />
              Сохранить все настройки
            </>
          )}
        </Button>
      </CardHeader>
      <CardContent>
        {error && (
          <div className="bg-red-50 text-red-800 p-4 mb-4 rounded-md flex items-start">
            <AlertCircle className="h-5 w-5 mr-2 flex-shrink-0" />
            <p>{error}</p>
          </div>
        )}
        
        {success && (
          <div className="bg-green-50 text-green-800 p-4 mb-4 rounded-md">
            {success}
          </div>
        )}
        
        {loading ? (
          <div className="flex flex-col items-center justify-center py-8">
            <Loader2 className="h-10 w-10 text-muted-foreground animate-spin mb-4" />
            <p className="text-muted-foreground">Загрузка настроек...</p>
          </div>
        ) : (
          <div className="border rounded-md">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-1/3">Настройка</TableHead>
                  <TableHead className="w-1/2">Значение</TableHead>
                  <TableHead className="w-1/6 text-right">Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {settings.map((setting, index) => (
                  <TableRow key={setting.name}>
                    <TableCell>
                      <div className="font-medium">{getSettingLabel(setting.name)}</div>
                      <div className="text-xs text-muted-foreground mt-1">{setting.name}</div>
                    </TableCell>
                    <TableCell>
                      {setting.isEditing ? (
                        <input
                          type="text"
                          value={setting.value}
                          onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleSettingChange(index, e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary"
                        />
                      ) : (
                        <span>{setting.value}</span>
                      )}
                    </TableCell>
                    <TableCell className="text-right">
                      <Button
                        variant={setting.isEditing ? "default" : "outline"}
                        size="sm"
                        onClick={() => toggleEditMode(index)}
                      >
                        {setting.isEditing ? 'Готово' : 'Изменить'}
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
                
                {settings.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={3} className="text-center py-8 text-muted-foreground">
                      Настройки не найдены
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default Settings; 