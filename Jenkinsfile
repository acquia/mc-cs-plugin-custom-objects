pipeline {
  options {
    skipDefaultCheckout()
  }
  agent {
    kubernetes {
      label 'mautic-hosted-build'
      inheritFrom 'with-mysql'
      containerTemplate {
        name 'hosted-tester'
        image 'us.gcr.io/mautic-ma/mautic_tester:master'
        ttyEnabled true
        command 'cat'
      }
    }
  }
  stages {
    stage('Download and combine') {
      steps {
        container('hosted-tester') {
          checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: 'deployed']], doGenerateSubmoduleConfigurations: false, extensions: [], submoduleCfg: [], userRemoteConfigs: [[credentialsId: '1a066462-6d24-4247-bef6-1da084c8f484', url: 'git@github.com:mautic-inc/mautic-cloud.git']]]
          sh('rm -rf plugins/IntegrationsBundle')
          checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: 'master']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'RelativeTargetDirectory', relativeTargetDir: 'plugins/IntegrationsBundle']], submoduleCfg: [], userRemoteConfigs: [[credentialsId: '1a066462-6d24-4247-bef6-1da084c8f484', url: 'git@github.com:mautic-inc/plugin-mautic-integrations.git']]]
          sh('mkdir -p plugins/CustomObjectsBundle && chmod 777 plugins/CustomObjectsBundle')
          dir('plugins/CustomObjectsBundle') {
            checkout scm
          }
        }
      }
    }
    stage('Build') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            sh """
              composer install --ansi
            """
            dir('plugins/CustomObjectsBundle') {
              sh("composer install --ansi")
            }
          }
        }
      }
    }
    stage('Styling') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            dir('plugins/CustomObjectsBundle') {
              sh """
                vendor/bin/ecs check .
              """
            }
          }
        }
      }
    }
    stage('Test') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            sh """
              mysql -h 127.0.0.1 -e 'CREATE DATABASE mautictest; CREATE USER travis@"%"; GRANT ALL on mautictest.* to travis@"%"; GRANT SUPER ON *.* TO travis@"%";'
              echo "<?php
              \\\$parameters = array(
                  'db_driver' => 'pdo_mysql',
                  'db_host' => '127.0.0.1',
                  'db_port' => 3306,
                  'db_name' => 'mautictest',
                  'db_user' => 'travis',
                  'db_password' => '',
                  'db_table_prefix' => '',
                  'hosted_plan' => 'pro'
              );" > app/config/local.php
              export SYMFONY_ENV="test"
              bin/phpunit -d memory_limit=2048M --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist --fail-on-warning --filter CustomObjectsBundle
            """
          }
        }
      }
    }
  }
}
