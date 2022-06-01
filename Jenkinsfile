// Uses Declarative syntax to run commands inside a container.
pipeline {
    triggers {
        pollSCM("*/5 * * * *")
    }
    agent {
        kubernetes {
            yaml '''
apiVersion: v1
kind: Pod
spec:
  containers:
    - name: php
      image: quay.imanuel.dev/dockerhub/library---php:8.1-cli
      command:
        - sleep
      args:
        - infinity
'''
            defaultContainer 'php'
        }
    }
    stages {
        stage('Install dependencies') {
            steps {
                sh "mkdir -p /usr/share/man/man1"
                sh "apt update"
                sh "apt install -y git wget libzip-dev sqlite3 libsqlite3-dev"
                sh "docker-php-ext-install zip"
                sh 'docker-php-ext-install pdo pdo_sqlite'
                sh 'pecl install pcov'
                sh 'docker-php-ext-enable pcov'
                sh "php --version"
                sh '''php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');"'''
                sh "php composer-setup.php"
                sh '''php -r "unlink(\'composer-setup.php\');"'''
                sh 'php composer.phar install'
            }
        }
        stage('Tests and liniting') {
            parallel {
                stage('Phpstan') {
                    steps {
                        sh './vendor/bin/phpstan --no-progress analyze ./src ./tests'
                    }
                }
                stage('PHPUnit') {
                    steps {
                        sh './vendor/bin/phpunit --log-junit ./report.xml --configuration ./phpunit.xml'
                    }
                }
            }
        }
    }
    post {
        always {
            junit 'report.xml'
        }
    }
}
