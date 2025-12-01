<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

#[AsCommand(
    name: '<?= $command_name; ?>',
    description: '<?= $command_description; ?>',
)]
class <?= $class_name; ?>
{
    public function __invoke(
        SymfonyStyle $io<?php
$params = [];
if (!is_array($command_parameters)) {
    $command_parameters = [];
}
foreach ($command_parameters as $param):
    $paramStr = '';
    $attrParams = [];
    
    if ('argument' === $param['param_type']) {
        if (!empty($param['description'])) {
            $attrParams[] = "description: '{$param['description']}'";
        }
        
        if (!empty($attrParams)) {
            $paramStr .= '#[Argument(' . implode(', ', $attrParams) . ')] ';
        } else {
            $paramStr .= '#[Argument] ';
        }
        
        $type = $param['type'];
        if ($param['nullable'] ?? false) {
            $type = '?' . $type;
        }
        $paramStr .= $type . ' $' . $param['name'];
        
        if (null !== $param['default'] || ($param['nullable'] ?? false)) {
            $default = $param['default'];
            if (null === $default) {
                $paramStr .= ' = null';
            } elseif (is_bool($default)) {
                $paramStr .= ' = ' . ($default ? 'true' : 'false');
            } elseif (is_array($default)) {
                $paramStr .= ' = []';
            } elseif (is_string($default)) {
                $paramStr .= " = '{$default}'";
            } else {
                $paramStr .= ' = ' . $default;
            }
        }
    } else {
        // option
        if (!empty($param['description'])) {
            $attrParams[] = "description: '{$param['description']}'";
        }
        if (!empty($param['shortcut'])) {
            $attrParams[] = "shortcut: '{$param['shortcut']}'";
        }
        
        if (!empty($attrParams)) {
            $paramStr .= '#[Option(' . implode(', ', $attrParams) . ')] ';
        } else {
            $paramStr .= '#[Option] ';
        }
        
        $paramStr .= $param['type'] . ' $' . $param['name'];
        $default = $param['default'];
        if (is_bool($default)) {
            $paramStr .= ' = ' . ($default ? 'true' : 'false');
        } elseif (is_array($default)) {
            $paramStr .= ' = []';
        } elseif (is_string($default)) {
            $paramStr .= " = '{$default}'";
        } else {
            $paramStr .= ' = ' . $default;
        }
    }
    $params[] = $paramStr;
endforeach;

if (!empty($params)) {
    echo ",\n        " . implode(",\n        ", $params);
}
?>,
    ): int
    {
<?php foreach ($command_parameters as $param): ?>
<?php if ('argument' === $param['param_type']): ?>
        if ($<?= $param['name']; ?>) {
            $io->note(sprintf('You passed an argument: %s', $<?= $param['name']; ?>));
        }

<?php else: ?>
        if ($<?= $param['name']; ?>) {
            $io->note(sprintf('You passed option: %s', $<?= $param['name']; ?>));
        }

<?php endif; ?>
<?php endforeach; ?>
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
