import React, { useState, useEffect } from 'react';
import { Save } from 'lucide-react';

// Интерфейс для настроек магазина
interface ShopSettings {
  general: {
    shop_name: string;
    shop_description: string;
    email: string;
    phone: string;
    address: string;
  };
  currency: {
    code: string;
    symbol: string;
    position: 'before' | 'after';
  };
  prices: {
    tax_included: boolean;
    tax_rate: number;
  };
  shipping: {
    free_shipping_enabled: boolean;
    free_shipping_min_amount: number;
    shipping_methods: {
      id: string;
      name: string;
      price: number;
      enabled: boolean;
    }[];
  };
  payment: {
    payment_methods: {
      id: string;
      name: string;
      enabled: boolean;
    }[];
  };
}

// Заглушка для API настроек
const defaultSettings: ShopSettings = {
  general: {
    shop_name: 'Universal Shop',
    shop_description: 'Интернет-магазин на базе Universal CMS',
    email: 'shop@example.com',
    phone: '+7 (123) 456-78-90',
    address: 'г. Москва, ул. Примерная, д. 123',
  },
  currency: {
    code: 'RUB',
    symbol: '₽',
    position: 'after',
  },
  prices: {
    tax_included: true,
    tax_rate: 20,
  },
  shipping: {
    free_shipping_enabled: true,
    free_shipping_min_amount: 5000,
    shipping_methods: [
      { id: 'courier', name: 'Курьер', price: 300, enabled: true },
      { id: 'pickup', name: 'Самовывоз', price: 0, enabled: true },
      { id: 'post', name: 'Почта России', price: 250, enabled: true },
      { id: 'cdek', name: 'СДЭК', price: 350, enabled: true },
    ],
  },
  payment: {
    payment_methods: [
      { id: 'card', name: 'Банковская карта', enabled: true },
      { id: 'cash', name: 'Наличные при получении', enabled: true },
      { id: 'qr', name: 'Оплата по QR-коду', enabled: false },
      { id: 'yandex', name: 'Яндекс Pay', enabled: false },
    ],
  },
};

const ShopSettings: React.FC = () => {
  const [settings, setSettings] = useState<ShopSettings | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [activeTab, setActiveTab] = useState('general');

  useEffect(() => {
    // Имитация загрузки данных
    const loadData = () => {
      setTimeout(() => {
        setSettings(defaultSettings);
        setLoading(false);
      }, 500);
    };

    loadData();
  }, []);

  // Обработчик изменения текстового поля
  const handleTextChange = (section: keyof ShopSettings, field: string, value: string) => {
    if (!settings) return;

    setSettings({
      ...settings,
      [section]: {
        ...settings[section],
        [field]: value,
      },
    });
  };

  // Обработчик изменения числового поля
  const handleNumberChange = (section: keyof ShopSettings, field: string, value: string) => {
    if (!settings) return;

    setSettings({
      ...settings,
      [section]: {
        ...settings[section],
        [field]: parseFloat(value) || 0,
      },
    });
  };

  // Обработчик изменения булевого поля
  const handleBooleanChange = (section: keyof ShopSettings, field: string, checked: boolean) => {
    if (!settings) return;

    setSettings({
      ...settings,
      [section]: {
        ...settings[section],
        [field]: checked,
      },
    });
  };

  // Обработчик изменения методов доставки
  const handleShippingMethodChange = (index: number, field: string, value: any) => {
    if (!settings) return;

    const updatedMethods = [...settings.shipping.shipping_methods];
    updatedMethods[index] = {
      ...updatedMethods[index],
      [field]: field === 'price' ? parseFloat(value) || 0 : value,
    };

    setSettings({
      ...settings,
      shipping: {
        ...settings.shipping,
        shipping_methods: updatedMethods,
      },
    });
  };

  // Обработчик изменения методов оплаты
  const handlePaymentMethodChange = (index: number, field: string, value: any) => {
    if (!settings) return;

    const updatedMethods = [...settings.payment.payment_methods];
    updatedMethods[index] = {
      ...updatedMethods[index],
      [field]: value,
    };

    setSettings({
      ...settings,
      payment: {
        ...settings.payment,
        payment_methods: updatedMethods,
      },
    });
  };

  // Обработчик сохранения настроек
  const handleSave = (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);

    // Имитация сохранения данных
    setTimeout(() => {
      alert('Настройки магазина успешно сохранены!');
      setSaving(false);
    }, 1000);
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center p-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  if (!settings) {
    return (
      <div className="bg-red-50 border border-red-200 text-red-800 p-4 rounded">
        <p>Ошибка загрузки настроек магазина.</p>
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Настройки магазина</h1>

      <div className="bg-white rounded-lg shadow-sm">
        {/* Вкладки настроек */}
        <div className="flex border-b">
          <button
            className={`px-4 py-2 font-medium text-sm ${
              activeTab === 'general' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:text-gray-700'
            }`}
            onClick={() => setActiveTab('general')}
          >
            Общие
          </button>
          <button
            className={`px-4 py-2 font-medium text-sm ${
              activeTab === 'currency' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:text-gray-700'
            }`}
            onClick={() => setActiveTab('currency')}
          >
            Валюта и цены
          </button>
          <button
            className={`px-4 py-2 font-medium text-sm ${
              activeTab === 'shipping' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:text-gray-700'
            }`}
            onClick={() => setActiveTab('shipping')}
          >
            Доставка
          </button>
          <button
            className={`px-4 py-2 font-medium text-sm ${
              activeTab === 'payment' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:text-gray-700'
            }`}
            onClick={() => setActiveTab('payment')}
          >
            Оплата
          </button>
        </div>

        <form onSubmit={handleSave}>
          <div className="p-6">
            {/* Общие настройки */}
            {activeTab === 'general' && (
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Название магазина
                  </label>
                  <input
                    type="text"
                    className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    value={settings.general.shop_name}
                    onChange={(e) => handleTextChange('general', 'shop_name', e.target.value)}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Описание магазина
                  </label>
                  <textarea
                    className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    rows={3}
                    value={settings.general.shop_description}
                    onChange={(e) => handleTextChange('general', 'shop_description', e.target.value)}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Email для связи
                  </label>
                  <input
                    type="email"
                    className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    value={settings.general.email}
                    onChange={(e) => handleTextChange('general', 'email', e.target.value)}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Телефон
                  </label>
                  <input
                    type="text"
                    className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    value={settings.general.phone}
                    onChange={(e) => handleTextChange('general', 'phone', e.target.value)}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Адрес
                  </label>
                  <input
                    type="text"
                    className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    value={settings.general.address}
                    onChange={(e) => handleTextChange('general', 'address', e.target.value)}
                  />
                </div>
              </div>
            )}

            {/* Настройки валюты и цен */}
            {activeTab === 'currency' && (
              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Код валюты
                    </label>
                    <input
                      type="text"
                      className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      value={settings.currency.code}
                      onChange={(e) => handleTextChange('currency', 'code', e.target.value)}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Символ валюты
                    </label>
                    <input
                      type="text"
                      className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      value={settings.currency.symbol}
                      onChange={(e) => handleTextChange('currency', 'symbol', e.target.value)}
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Положение символа валюты
                  </label>
                  <select
                    className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    value={settings.currency.position}
                    onChange={(e) => handleTextChange('currency', 'position', e.target.value as 'before' | 'after')}
                  >
                    <option value="before">Перед суммой (₽100)</option>
                    <option value="after">После суммы (100₽)</option>
                  </select>
                </div>
                <div className="mt-6">
                  <h3 className="text-lg font-medium text-gray-900 mb-2">Налоги</h3>
                  <div className="space-y-3">
                    <div className="flex items-center">
                      <input
                        type="checkbox"
                        id="tax_included"
                        className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        checked={settings.prices.tax_included}
                        onChange={(e) => handleBooleanChange('prices', 'tax_included', e.target.checked)}
                      />
                      <label htmlFor="tax_included" className="ml-2 block text-sm text-gray-900">
                        Цены включают налог (НДС)
                      </label>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Ставка налога (%)
                      </label>
                      <input
                        type="number"
                        className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        value={settings.prices.tax_rate}
                        onChange={(e) => handleNumberChange('prices', 'tax_rate', e.target.value)}
                        min="0"
                        max="100"
                        step="0.1"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Настройки доставки */}
            {activeTab === 'shipping' && (
              <div className="space-y-4">
                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="free_shipping_enabled"
                    className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    checked={settings.shipping.free_shipping_enabled}
                    onChange={(e) => handleBooleanChange('shipping', 'free_shipping_enabled', e.target.checked)}
                  />
                  <label htmlFor="free_shipping_enabled" className="ml-2 block text-sm text-gray-900">
                    Включить бесплатную доставку при минимальной сумме заказа
                  </label>
                </div>
                {settings.shipping.free_shipping_enabled && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Минимальная сумма заказа для бесплатной доставки
                    </label>
                    <div className="flex">
                      <input
                        type="number"
                        className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        value={settings.shipping.free_shipping_min_amount}
                        onChange={(e) => handleNumberChange('shipping', 'free_shipping_min_amount', e.target.value)}
                        min="0"
                        step="100"
                      />
                      <span className="ml-2 p-2 text-gray-500">{settings.currency.symbol}</span>
                    </div>
                  </div>
                )}

                <h3 className="text-lg font-medium text-gray-900 mb-2 mt-6">Способы доставки</h3>
                {settings.shipping.shipping_methods.map((method, index) => (
                  <div key={method.id} className="border p-4 rounded-md">
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center">
                        <input
                          type="checkbox"
                          id={`shipping_method_${method.id}`}
                          className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                          checked={method.enabled}
                          onChange={(e) => handleShippingMethodChange(index, 'enabled', e.target.checked)}
                        />
                        <label htmlFor={`shipping_method_${method.id}`} className="ml-2 block text-sm font-medium text-gray-900">
                          {method.name}
                        </label>
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Стоимость доставки
                      </label>
                      <div className="flex">
                        <input
                          type="number"
                          className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          value={method.price}
                          onChange={(e) => handleShippingMethodChange(index, 'price', e.target.value)}
                          min="0"
                          step="10"
                          disabled={!method.enabled}
                        />
                        <span className="ml-2 p-2 text-gray-500">{settings.currency.symbol}</span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* Настройки оплаты */}
            {activeTab === 'payment' && (
              <div className="space-y-4">
                <h3 className="text-lg font-medium text-gray-900 mb-2">Способы оплаты</h3>
                {settings.payment.payment_methods.map((method, index) => (
                  <div key={method.id} className="flex items-center p-3 border rounded-md">
                    <input
                      type="checkbox"
                      id={`payment_method_${method.id}`}
                      className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      checked={method.enabled}
                      onChange={(e) => handlePaymentMethodChange(index, 'enabled', e.target.checked)}
                    />
                    <label htmlFor={`payment_method_${method.id}`} className="ml-2 block text-sm font-medium text-gray-900">
                      {method.name}
                    </label>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="px-6 py-3 bg-gray-50 border-t rounded-b-lg flex justify-end">
            <button
              type="submit"
              className="bg-indigo-600 text-white px-4 py-2 rounded-md flex items-center hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
              disabled={saving}
            >
              {saving ? (
                <>
                  <div className="animate-spin h-4 w-4 mr-2 border-b-2 border-white rounded-full"></div>
                  Сохранение...
                </>
              ) : (
                <>
                  <Save className="w-4 h-4 mr-2" />
                  Сохранить настройки
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ShopSettings; 