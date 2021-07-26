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
  volumes:
    - name: docker-sock
      hostPath:
        path: /var/run/docker.sock
  containers:
    - name: php
      image: quay.imanuel.dev/dockerhub/library---php:8-cli
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
                sh "apt-get update"
                sh "apt-get install -y apt-utils"
                sh "apt-get install -y libzip-dev git wget unzip zip sqlite3"
                sh "docker-php-ext-install pdo pdo_sqlite zip"
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
                stage('Psalm') {
                    steps {
                        sh './vendor/bin/psalm'
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
