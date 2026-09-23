version: 2
updates:
    - package-ecosystem: composer
      directory: /
      schedule:
          interval: weekly
      groups:
          composer:
              patterns: ['*']
    - package-ecosystem: github-actions
      directory: /
      schedule:
          interval: weekly
      groups:
          github-actions:
              patterns: ['*']
