@Library('jenkins-scripts') _

pipeline {
  environment {
    SUBMODULE_NAME = "CustomObjectsBundle"
  }
  options {
    skipDefaultCheckout()
    disableConcurrentBuilds()
  }
  agent {
    kubernetes {
      inheritFrom 'with-mysql'
      yaml libraryResource('mautic-tester.yaml')
    }
  }
  stages {
    stage('Download and combine') {
      steps {
        container('mautic-tester') {
          checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: 'development']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'SubmoduleOption', disableSubmodules: false, parentCredentials: true, recursiveSubmodules: true]], submoduleCfg: [], userRemoteConfigs: [[credentialsId: '1a066462-6d24-4247-bef6-1da084c8f484', url: 'git@github.com:mautic-inc/mautic-cloud.git']]]
          sh('rm -r plugins/${SUBMODULE_NAME} || true; mkdir -p plugins/${SUBMODULE_NAME} && chmod 777 plugins/${SUBMODULE_NAME}')
          dir("plugins/${env.SUBMODULE_NAME}") {
            checkout scm
          }
        }
      }
    }
    stage('Build') {
      steps {
        container('mautic-tester') {
          ansiColor('xterm') {
            withCredentials([string(credentialsId: 'github-composer-token', variable: 'composertoken')]) {
              sh("composer config --global github-oauth.github.com ${composertoken}")
            }
            sh '''
              ## We need all private plugins enabled during tests so their tests can run successfully
              echo "<?php
              \\$hostedParameters = array_merge(
                  \\$hostedParameters,
                  [
                      'private_cloud_plugin_deny_list'  => [],
                      'private_cloud_plugin_allow_list' => [],
                  ]
              );" > app/config/hosted_local.php

              echo "<?php
                \\$parameters = array(
                    'db_driver' => 'pdo_mysql',
                    'db_host' => '127.0.0.1',
                    'db_port' => 3306,
                    'db_name' => 'mautictest',
                    'db_user' => 'travis',
                    'db_password' => '',
                    'db_table_prefix' => '',
                    'hosted_plan' => 'pro',
                    'custom_objects_enabled' => true,
                    'create_custom_field_in_background' => false,
                );" > app/config/local.php
                composer validate --no-check-all --strict || (echo "Composer failed validation. If the lock file is out of sync you can try running 'composer update --lock'"; exit 1)
                composer install --ansi
            '''
          }
        }
      }
    }
    stage('Tests') {
      parallel {
        stage('PHPUNIT & Sonar Scan') {
          environment {
            CI_PULL_REQUEST = "${env.CHANGE_ID}"
            CI_BRANCH = "${env.BRANCH_NAME}"
            CI_BUILD_URL = "${env.BUILD_URL}"
            SCANNER_HOME = tool 'SonarQubeScanner'
            JAVA_HOME = tool 'AutoJavaInstallation'
            PATH = "${JAVA_HOME}/bin:${PATH}"
          }    
          steps {
            container('mautic-tester') {
              ansiColor('xterm') {
                sh '''
                  echo "PHP Version Info"
                  php --version

                  mysql -h 127.0.0.1 -e 'CREATE DATABASE mautictest; CREATE USER travis@"%"; GRANT ALL on mautictest.* to travis@"%"; GRANT SUPER,PROCESS ON *.* TO travis@"%";'
                  export SYMFONY_ENV="test"
                  export AGENT_HOME=`pwd`

                  pwd
                  mkdir -p "${AGENT_HOME}/var/cache/coverage-report"
                  # APP_DEBUG=0 disables debug mode for functional test clients decreasing memory usage to almost half
                  cd ./plugins/${SUBMODULE_NAME}/
                  APP_DEBUG=0 php -dpcov.enabled=1 -dpcov.directory=. -dpcov.exclude="~tests|themes|vendor~" ../../bin/phpunit -d memory_limit=1G --bootstrap ../../vendor/autoload.php --configuration phpunit.xml --disallow-test-output --coverage-clover ${AGENT_HOME}/var/cache/coverage-report/clover.xml --testsuite=all
                '''
                withSonarQubeEnv('SonarqubeServer') {
                  sh '''
                     ls -ltrh
                     pwd
                     cat ${AGENT_HOME}/var/cache/coverage-report/clover.xml | head -10
                     pwd
                     find ${AGENT_HOME} -type f -name "sonar-project.properties"
                     cd ${AGENT_HOME}/plugins/${SUBMODULE_NAME}/
                     pwd
                     ls -ltrh
                     $SCANNER_HOME/bin/sonar-scanner -Dproject.settings=./sonar-project.properties
                  '''
                }
              }
            }
          }
        }
        stage('Static Analysis') {
          steps {
            container('mautic-tester') {
              ansiColor('xterm') {
                dir("plugins/${env.SUBMODULE_NAME}") {
                  sh '''
                    composer phpstan -- --no-progress
                  '''
                }
              }
            }
          }
        }
        stage('CS Fixer') {
          steps {
            container('mautic-tester') {
              ansiColor('xterm') {
                dir("plugins/${env.SUBMODULE_NAME}") {
                  sh '''
                    composer csfixer
                  '''
                }
              }
            }
          }
        }
      }
    }
    stage("Quality Gate") {
      steps {
        timeout(time: 1, unit: 'HOURS') {
          waitForQualityGate abortPipeline: true
        }
      }
    }
    stage('Automerge') {
      when {
        anyOf {
          changeRequest target: 'staging'
          changeRequest target: 'master'
          changeRequest target: 'preproduction'
          changeRequest target: 'hotfix'
          changeRequest target: 'deployed'
          changeRequest target: 'epic-.*', comparator: 'REGEXP'
        }
      }
      steps {
        script {
          automergeScript()
        }
      }
    }
    stage('Set Revision') {
      when {
        not {
          changeRequest()
        }
        anyOf {
          branch 'development'
          branch 'beta';
          branch 'staging';
        }
      }
      steps {
        script {
          setRevisionScript()
        }
      }
    }
  }
  post {
    failure {
      script {
        postFailureScript()
      }
    }
    fixed {
      script {
        postFixedScript()
      }
    }
  }
}
