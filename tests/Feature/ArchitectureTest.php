<?php

/*
| Module dependency direction. An upstream module must never import a downstream module's classes; when it needs
| something from downstream it defines a contract and asks the registry with Modules::providing(...).
|
|   Core ← Accounts ← Academic ← Teachers / Students ← Exams ← Fees / Results
|   Diary: Students + Teachers.   Notices, Gallery, Admission: Core only.
|   Website, StudentPortal, TeacherPanel are consumers and may depend on anything.
*/

const MODULE_DEPENDENCIES = [
    'Core' => [],
    'Accounts' => ['Core'],
    'Academic' => ['Core', 'Accounts'],
    'Teachers' => ['Core', 'Accounts', 'Academic'],
    'Students' => ['Core', 'Accounts', 'Academic'],
    'Exams' => ['Core', 'Accounts', 'Academic', 'Students'],
    'Fees' => ['Core', 'Accounts', 'Academic', 'Students', 'Exams'],
    'Results' => ['Core', 'Accounts', 'Academic', 'Students', 'Exams', 'Teachers'],
    'Diary' => ['Core', 'Accounts', 'Academic', 'Students', 'Teachers'],
    'Notices' => ['Core'],
    'Gallery' => ['Core'],
    'Admission' => ['Core'],
    'Website' => '*',
    'StudentPortal' => '*',
    'TeacherPanel' => '*',
];

/**
 * @return array<string, list<string>> module => modules it references in non-test PHP/Blade code
 */
function moduleReferences(): array
{
    $references = [];

    foreach (glob(base_path('Modules/*'), GLOB_ONLYDIR) as $directory) {
        $module = basename($directory);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            $path = $file->getPathname();

            if (! preg_match('/\.(php)$/', $path) || str_contains($path, '/Tests/') || str_contains($path, '/ai-workflow/')) {
                continue;
            }

            preg_match_all('/Modules\\\\(\w+)\\\\/', file_get_contents($path), $matches);

            foreach (array_unique($matches[1]) as $referenced) {
                if ($referenced !== $module) {
                    $references[$module][$referenced][] = str_replace(base_path().'/', '', $path);
                }
            }
        }
    }

    return $references;
}

it('knows every module in Modules/', function () {
    $modules = array_map('basename', glob(base_path('Modules/*'), GLOB_ONLYDIR));

    expect(array_diff($modules, array_keys(MODULE_DEPENDENCIES)))->toBe([], 'Add the new module to MODULE_DEPENDENCIES in ArchitectureTest.');
});

it('only lets a module depend on modules upstream of it', function () {
    $violations = [];

    foreach (moduleReferences() as $module => $referenced) {
        $allowed = MODULE_DEPENDENCIES[$module] ?? [];

        if ($allowed === '*') {
            continue;
        }

        foreach ($referenced as $other => $files) {
            if (! in_array($other, $allowed, true)) {
                $violations[] = "{$module} must not use {$other} (in ".implode(', ', array_slice($files, 0, 2)).')';
            }
        }
    }

    expect($violations)->toBe([]);
});

it('registers every module in bootstrap/modules.php', function () {
    $registered = array_map(fn (string $class): string => explode('\\', $class)[1] ?? '', require base_path('bootstrap/modules.php'));
    $present = array_map('basename', glob(base_path('Modules/*'), GLOB_ONLYDIR));

    expect(array_diff($present, $registered))->toBe([]);
});

it('keeps the registered order consistent with the dependency direction', function () {
    $order = array_values(array_filter(array_map(fn (string $class): string => explode('\\', $class)[1] ?? '', require base_path('bootstrap/modules.php')), fn (string $name) => isset(MODULE_DEPENDENCIES[$name])));

    foreach ($order as $position => $module) {
        $dependencies = MODULE_DEPENDENCIES[$module];

        if ($dependencies === '*') {
            continue;
        }

        foreach ($dependencies as $dependency) {
            expect(array_search($dependency, $order, true))->toBeLessThan($position, "{$dependency} must be listed before {$module}");
        }
    }
});
