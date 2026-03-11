#!/bin/bash

# QMS API - Test Runner Script
# Удобные команды для запуска различных тестов

set -e

# Цвета для вывода
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Функции вывода
print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Основные команды
run_all_tests() {
    print_header "Запуск всех тестов"
    php artisan test
    print_success "Все тесты пройдены!"
}

run_feature_tests() {
    print_header "Запуск Feature тестов"
    php artisan test --testsuite=Feature --env=testing
    print_success "Feature тесты пройдены!"
}

run_unit_tests() {
    print_header "Запуск Unit тестов"
    php artisan test --testsuite=Unit --env=testing
    print_success "Unit тесты пройдены!"
}

run_bdd_tests() {
    print_header "Запуск BDD тестов (Behat)"
    vendor/bin/behat
    print_success "BDD тесты пройдены!"
}

run_with_coverage() {
    print_header "Запуск тестов с code coverage"
    php artisan test --env=testing --coverage --coverage-clover=coverage.xml
    print_success "Code coverage отчет создан: coverage.xml"
}

run_auth_tests() {
    print_header "Запуск тестов аутентификации"
    php artisan test tests/Feature/AuthenticationTest.php --env=testing
    print_success "Тесты аутентификации пройдены!"
}

run_chat_tests() {
    print_header "Запуск тестов чатов"
    php artisan test tests/Feature/ChatTest.php --env=testing
    print_success "Тесты чатов пройдены!"
}

run_message_tests() {
    print_header "Запуск тестов сообщений"
    php artisan test tests/Feature/MessageAndAttachmentTest.php --env=testing
    print_success "Тесты сообщений пройдены!"
}

run_call_tests() {
    print_header "Запуск тестов звонков"
    php artisan test tests/Feature/WebRTCCallTest.php --env=testing
    print_success "Тесты звонков пройдены!"
}

run_parallel_tests() {
    print_header "Запуск тестов параллельно"
    php artisan test --parallel --processes=4 --env=testing
    print_success "Параллельные тесты пройдены!"
}

run_specific_test() {
    if [ -z "$1" ]; then
        print_error "Укажите название теста"
        echo "Пример: ./test.sh test test_user_can_register_and_receive_jwt_token"
        exit 1
    fi

    print_header "Запуск конкретного теста: $1"
    php artisan test --filter="$1" --env=testing
    print_success "Тест завершен!"
}

run_debug_test() {
    if [ -z "$1" ]; then
        print_error "Укажите путь до теста"
        echo "Пример: ./test.sh debug tests/Feature/AuthenticationTest.php"
        exit 1
    fi

    print_header "Debug режим для: $1"
    php artisan test "$1" --debug --env=testing
    print_success "Debug завершен!"
}

watch_tests() {
    print_header "Наблюдение за тестами (требует Watchman)"
    # Требует установки Watchman
    watch -n 2 "php artisan test --testsuite=Unit"
}

setup_testing_env() {
    print_header "Подготовка тестового окружения"

    print_info "Установка зависимостей..."
    composer install --prefer-dist

    print_info "Генерирование ключа приложения..."
    php artisan key:generate --env=testing

    print_info "Генерирование JWT secret..."
    php artisan jwt:secret --env=testing || true

    print_info "Проверка .env.testing файла..."
    if [ ! -f .env.testing ]; then
        print_error ".env.testing не найден. Создайте его на основе .env.testing.example"
        exit 1
    fi

    print_info "Создание тестовой БД qms_test (если не существует)..."
    docker compose exec qms-db psql -U qms_user -d postgres -tc \
        "SELECT 1 FROM pg_database WHERE datname='qms_test'" | grep -q 1 || \
    docker compose exec qms-db psql -U qms_user -d postgres -c "CREATE DATABASE qms_test;"

    print_info "Выполнение миграций для тестов (PostgreSQL qms_test)..."
    php artisan migrate:fresh --env=testing --force --no-interaction

    print_success "Тестовое окружение готово!"
    print_info "Конфигурация: PostgreSQL qms_test из .env.testing"
}

clean_tests() {
    print_header "Очистка тестовых артефактов"

    print_info "Удаление coverage отчета..."
    rm -f coverage.xml coverage/

    print_info "Очистка временных файлов..."
    rm -rf storage/logs/testing.log

    print_info "Очистка кэша..."
    php artisan cache:clear --env=testing

    print_success "Очистка завершена!"
}

list_tests() {
    print_header "Доступные тесты"

    echo ""
    echo -e "${YELLOW}Feature Tests:${NC}"
    find tests/Feature -name "*.php" -type f | sed 's|tests/Feature/||' | sed 's|\.php||'

    echo ""
    echo -e "${YELLOW}Unit Tests:${NC}"
    find tests/Unit -name "*.php" -type f | sed 's|tests/Unit/||' | sed 's|\.php||'

    echo ""
    echo -e "${YELLOW}BDD Features:${NC}"
    find tests/features -name "*.feature" -type f | sed 's|tests/features/||'
}

show_help() {
    cat << EOF
${BLUE}QMS API - Test Runner${NC}

Использование: ./test.sh [команда] [опции]

Команды:
    all              - Запустить все тесты
    feature          - Запустить только Feature тесты
    unit             - Запустить только Unit тесты
    bdd              - Запустить BDD тесты (Behat)
    coverage         - Запустить тесты с code coverage

    auth             - Тесты аутентификации
    chat             - Тесты чатов
    message          - Тесты сообщений
    call             - Тесты звонков

    parallel         - Параллельное выполнение тестов
    test <имя>       - Запустить конкретный тест
    debug <путь>     - Запустить тест в debug режиме
    watch            - Наблюдение за тестами (требует Watchman)

    setup            - Подготовить тестовое окружение
    clean            - Очистить тестовые артефакты
    list             - Список всех доступных тестов

    help             - Показать эту справку

Примеры:
    ./test.sh all                    # Все тесты
    ./test.sh auth                   # Тесты аутентификации
    ./test.sh test auth              # Тесты с "auth" в названии
    ./test.sh debug tests/Feature/AuthenticationTest.php
    ./test.sh coverage               # С code coverage
    ./test.sh setup                  # Подготовка окружения

${GREEN}Готово!${NC}
EOF
}

# Парсинг аргументов
case "${1:-help}" in
    all)
        run_all_tests
        ;;
    feature)
        run_feature_tests
        ;;
    unit)
        run_unit_tests
        ;;
    bdd)
        run_bdd_tests
        ;;
    coverage)
        run_with_coverage
        ;;
    auth)
        run_auth_tests
        ;;
    chat)
        run_chat_tests
        ;;
    message)
        run_message_tests
        ;;
    call)
        run_call_tests
        ;;
    parallel)
        run_parallel_tests
        ;;
    test)
        run_specific_test "$2"
        ;;
    debug)
        run_debug_test "$2"
        ;;
    watch)
        watch_tests
        ;;
    setup)
        setup_testing_env
        ;;
    clean)
        clean_tests
        ;;
    list)
        list_tests
        ;;
    help)
        show_help
        ;;
    *)
        print_error "Неизвестная команда: $1"
        show_help
        exit 1
        ;;
esac

