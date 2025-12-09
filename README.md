# Система бронирования ресурсов

Система бронирования ресурсов на **Symfony** с интеграцией **Redis**, **MySQL** и **JWT аутентификацией**.

## Технологии

- **Symfony 6.4+** — PHP фреймворк  
- **Doctrine ORM + Migrations** — работа с базой данных  
- **MySQL** — основная база данных  
- **Redis** — хранение сессий и кэширование списка товаров  
- **RabbitMQ** — асинхронная обработка событий (регистрация, CRUD операции)  
- **Docker & Docker Compose** — контейнеризация и оркестрация  
- **PHPUnit** — интеграционные и юнит-тесты с покрытием ≥ 60%  
- **OpenAPI/Swagger** — автоматическая документация API  

---

## Быстрый старт

```bash
# 1. Настраиваем окружение
git clone https://github.com/urmatovnaa/booking-system.git
cd booking-system
cp .env.example .env

# 2. Запускаем сервисы
docker-compose up -d --build

# 3. Устанавливаем зависимости
docker-compose exec php composer install

# 4. Применяем миграции
docker-compose exec php php bin/console doctrine:migrations:migrate -n

# 5. Проверяем API
open http://localhost:8080
```

## Миграции
```bash
# Создать новую миграцию:
docker-compose exec php php bin/console make:migration

# Применить миграции:
docker-compose exec php php bin/console doctrine:migrations:migrate -n

# Откатить последнюю миграцию:
docker-compose exec php php bin/console doctrine:migrations:migrate prev -n

# Статус миграций:
docker-compose exec php php bin/console doctrine:migrations:status
```

## Тестирование
```bash
# Создать тестовую БД:
docker-compose exec php php bin/console doctrine:database:create --env=test

# Применить миграцию для тестовой БД:
docker-compose exec php php bin/console doctrine:migrations:migrate --env=test -n

# Запуск всех тестов:
docker-compose exec php php bin/phpunit

# Запуск с покрытием кода:
docker-compose exec php php bin/phpunit --coverage-html=public/coverage

# Просмотр отчёта
open http://localhost:8000/coverage

# Запуск конкретного теста:
docker-compose exec php php bin/phpunit tests/Integration/Controller/BookingControllerTest.php
```

## Примеры запросов API

### Регистрация пользователя
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "name": "John Doe"
  }'
```
### Авторизация
```bash
curl -X POST http://localhost:8000/api/auth \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```
### Создание ресурса
```bash
curl -X POST http://localhost:8000/api/resources \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "name": "Конференц-зал A",
    "description": "Большой зал с проектором",
    "capacity": 50
  }'
```
### Создание ресурса
```bash
curl -X POST http://localhost:8000/api/bookings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "resourceId": 1,
    "startTime": "2024-01-20T10:00:00+03:00",
    "endTime": "2024-01-20T12:00:00+03:00"
  }'
```
## Сервисы
- **Веб-интерфейс:** http://localhost:8080
- **API:** http://localhost:8080/api/...
- **Swagger UI:** http://localhost:8080/api/doc
- **RabbitMQ Management:** http://localhost:15672 (guest / guest)
- **MySQL:** localhost:3306 (user: app, pass: app)
- **Redis:** localhost:6379
