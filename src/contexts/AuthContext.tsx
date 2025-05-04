import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { authAPI, User } from '../services/api';

interface AuthContextProps {
  user: User | null;
  isLoading: boolean;
  error: string | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextProps>({
  user: null,
  isLoading: false,
  error: null,
  login: async () => {},
  logout: () => {},
  isAuthenticated: false,
});

export const useAuth = () => useContext(AuthContext);

interface AuthProviderProps {
  children: ReactNode;
}

// Функция для декодирования JWT токена
const decodeJWT = (token: string): any | null => {
  try {
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split('')
        .map((c) => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    );
    return JSON.parse(jsonPayload);
  } catch (error) {
    console.error('Ошибка при декодировании JWT токена:', error);
    return null;
  }
};

// Функция для проверки срока действия токена
const isTokenExpired = (token: string): boolean => {
  try {
    const decodedToken = decodeJWT(token);
    if (!decodedToken) return true;
    
    const currentTime = Math.floor(Date.now() / 1000);
    console.log('Проверка срока действия токена:', {
      exp: decodedToken.exp,
      current: currentTime,
      diff: decodedToken.exp - currentTime
    });
    
    return decodedToken.exp < currentTime;
  } catch (error) {
    console.error('Ошибка при проверке срока действия токена:', error);
    return true; // В случае ошибки считаем токен истекшим
  }
};

export const AuthProvider = ({ children }: AuthProviderProps) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Сохраняем данные авторизации
  const saveAuthData = (userData: User, token: string) => {
    try {
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(userData));
      console.log('Данные аутентификации сохранены в localStorage');
    } catch (err) {
      console.error('Ошибка при сохранении данных аутентификации:', err);
    }
  };
  
  // Очищаем данные авторизации
  const clearAuthData = () => {
    try {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      console.log('Данные аутентификации удалены из localStorage');
    } catch (err) {
      console.error('Ошибка при удалении данных аутентификации:', err);
    }
  };
  
  // Сохраняем пользователя в localStorage при изменении состояния
  useEffect(() => {
    if (user) {
      try {            
        localStorage.setItem('user', JSON.stringify(user));
        console.log('Данные пользователя обновлены в localStorage');
      } catch (err) {
        console.error('Ошибка при сохранении данных пользователя:', err);
      }
    }
  }, [user]);

  useEffect(() => {
    const initAuth = async () => {
      console.log('Инициализация AuthContext...');
      
      try {
        const token = localStorage.getItem('token');
        const userJson = localStorage.getItem('user');
        
        console.log('Проверка наличия данных аутентификации:', {
          hasToken: !!token,
          hasUserData: !!userJson
        });
        
        if (token && userJson) {
          // Сначала проверяем истечение срока действия токена на клиенте
          if (isTokenExpired(token)) {
            console.log('Токен истек - выполняем выход');
            clearAuthData();
            setUser(null);
            setIsLoading(false);
            return;
          }
          
          // Если токен не истек, пробуем восстановить пользователя из localStorage
          try {
            const userData = JSON.parse(userJson);
            console.log('Данные пользователя успешно восстановлены из localStorage');
            setUser(userData);
            
            // Проверяем валидность токена через запрос к API
            console.log('Валидация токена через API...');
            try {
              const response = await authAPI.me();
              console.log('Токен успешно проверен через API, пользователь:', response.data);
              setUser(response.data);
            } catch (apiErr: any) {
              console.error('Ошибка валидации токена:', apiErr);
              
              // Если ошибка связана с недействительным токеном
              if (apiErr.response && apiErr.response.status === 401) {
                console.log('Токен недействителен - выполняем выход');
                clearAuthData();
                setUser(null);
              } else {
                console.log('Ошибка сети/сервера - сохраняем локальные данные аутентификации');
                // Если ошибка не связана с токеном, просто используем данные из localStorage
              }
            }
          } catch (parseErr) {
            console.error('Ошибка при разборе данных пользователя:', parseErr);
            clearAuthData();
            setUser(null);
          }
        } else {
          console.log('Данные аутентификации не найдены');
          clearAuthData(); // На всякий случай очищаем все
          setUser(null);
        }
      } catch (err) {
        console.error('Критическая ошибка при инициализации:', err);
        clearAuthData();
        setUser(null);
      } finally {
        setIsLoading(false);
        console.log('Инициализация AuthContext завершена');
      }
    };
    
    initAuth();
    
    // Устанавливаем интервал для периодической проверки токена
    const tokenCheckInterval = setInterval(() => {
      const token = localStorage.getItem('token');
      if (token && isTokenExpired(token)) {
        console.log('Токен истек при периодической проверке - выполняем выход');
        clearAuthData();
        setUser(null);
      }
    }, 60000); // Проверка каждую минуту
    
    return () => {
      clearInterval(tokenCheckInterval);
    };
  }, []);

  const login = async (email: string, password: string) => {
    setIsLoading(true);
    setError(null);
    
    try {
      console.log('Попытка входа для пользователя:', email);
      const response = await authAPI.login({ email, password });
      console.log('Вход выполнен успешно:', response.data);
      
      const { user, token } = response.data;
      
      // Сохраняем данные в localStorage
      saveAuthData(user, token);
      
      // Обновляем состояние в контексте
      setUser(user);
      console.log('Пользователь авторизован:', user.email);
    } catch (err: any) {
      console.error('Ошибка входа:', err);
      if (err.response && err.response.data && err.response.data.error) {
        setError(err.response.data.error);
      } else {
        setError('Произошла ошибка при входе в систему');
      }
      throw err;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = () => {
    clearAuthData();
    setUser(null);
    console.log('Выход из системы выполнен');
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        error,
        login,
        logout,
        isAuthenticated: !!user,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}; 