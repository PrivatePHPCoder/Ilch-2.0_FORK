<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

/*
 * Sample data of the fitness module, used by Service\SampleData.
 *
 * Texts are given in German and English, the admin's language is used. Entries refer to each
 * other by their key (for example 'squat'). Images are drawings that come with the module in
 * static/sample/ (exercises: <key>.svg, programs: the name in 'image'), videos are tutorials on YouTube.
 */

// One session of a program: workout key, title, day (1 = Monday ... 7 = Sunday), optional.
$session = static fn (string $workout, array $title, ?int $day, bool $optional = false): array => [
    'workout' => $workout,
    'title' => $title,
    'day' => $day,
    'optional' => $optional,
];

$trainingA = ['de' => 'Training A', 'en' => 'Workout A'];
$trainingB = ['de' => 'Training B', 'en' => 'Workout B'];
$trainingC = ['de' => 'Training C', 'en' => 'Workout C'];
$cardio = ['de' => 'Cardio', 'en' => 'Cardio'];
$mobility = ['de' => 'Mobilität', 'en' => 'Mobility'];

// Weeks of a program: every week gets the sessions the callback returns for its number.
$weeks = static function (int $count, callable $sessionsOfWeek, callable $descriptionOfWeek): array {
    $phases = [];
    for ($week = 1; $week <= $count; $week++) {
        $phases[] = [
            'title' => ['de' => 'Woche ' . $week, 'en' => 'Week ' . $week],
            'description' => $descriptionOfWeek($week),
            'sessions' => $sessionsOfWeek($week),
        ];
    }

    return $phases;
};

return [
    'categories' => [
        'strength' => ['de' => 'Kraft', 'en' => 'Strength'],
        'cardio' => ['de' => 'Ausdauer', 'en' => 'Cardio'],
        'mobility' => ['de' => 'Beweglichkeit', 'en' => 'Mobility'],
    ],

    'muscleGroups' => [
        'legs' => ['de' => 'Beine', 'en' => 'Legs'],
        'glutes' => ['de' => 'Gesäß', 'en' => 'Glutes'],
        'chest' => ['de' => 'Brust', 'en' => 'Chest'],
        'back' => ['de' => 'Rücken', 'en' => 'Back'],
        'shoulders' => ['de' => 'Schultern', 'en' => 'Shoulders'],
        'arms' => ['de' => 'Arme', 'en' => 'Arms'],
        'core' => ['de' => 'Rumpf', 'en' => 'Core'],
    ],

    'exercises' => [
        'squat' => [
            'title' => ['de' => 'Kniebeuge', 'en' => 'Bodyweight squat'],
            'category' => 'strength',
            'difficulty' => 1,
            'primary' => 'legs',
            'muscles' => ['glutes', 'core'],
            'description' => [
                'de' => '<p>Die Kniebeuge ist die Grundübung für kräftige Beine und ein starkes Gesäß. Sie braucht kein Gerät und lässt sich überall machen.</p>',
                'en' => '<p>The squat is the basic exercise for strong legs and glutes. It needs no equipment and can be done anywhere.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Stell dich etwas breiter als hüftbreit hin, die Fußspitzen zeigen leicht nach außen.</li><li>Schieb die Hüfte nach hinten und beuge die Knie, als würdest du dich auf einen Stuhl setzen.</li><li>Geh so tief, bis die Oberschenkel etwa waagerecht sind. Brust bleibt oben, Fersen bleiben am Boden.</li><li>Drück dich über die ganze Fußsohle wieder nach oben.</li></ol>',
                'en' => '<ol><li>Stand a little wider than hip width, toes pointing slightly outwards.</li><li>Push your hips back and bend your knees as if sitting down on a chair.</li><li>Go down until your thighs are about level with the floor. Keep your chest up and your heels on the ground.</li><li>Push through your whole foot to stand up again.</li></ol>',
            ],
            'notes' => [
                'de' => 'Die Knie zeigen in Richtung der Fußspitzen und knicken nicht nach innen.',
                'en' => 'Keep your knees in line with your toes, don\'t let them cave in.',
            ],
            'video' => 'https://www.youtube.com/watch?v=l83R5PblSMA',
        ],
        'pushup' => [
            'title' => ['de' => 'Liegestütz', 'en' => 'Push-up'],
            'category' => 'strength',
            'difficulty' => 2,
            'primary' => 'chest',
            'muscles' => ['arms', 'shoulders', 'core'],
            'description' => [
                'de' => '<p>Liegestütze kräftigen Brust, Schultern und Arme und fordern gleichzeitig die Rumpfmuskulatur.</p>',
                'en' => '<p>Push-ups strengthen chest, shoulders and arms and train your core at the same time.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Hände etwas breiter als schulterbreit aufsetzen, Arme gestreckt.</li><li>Der Körper bildet von Kopf bis Fuß eine gerade Linie, Bauch und Gesäß sind angespannt.</li><li>Beuge die Arme, bis die Brust knapp über dem Boden ist. Die Ellbogen zeigen schräg nach hinten.</li><li>Drück dich kontrolliert wieder hoch.</li></ol>',
                'en' => '<ol><li>Place your hands a little wider than shoulder width, arms straight.</li><li>Your body forms a straight line from head to feet, abs and glutes tight.</li><li>Bend your arms until your chest is just above the floor. Elbows point diagonally backwards.</li><li>Push yourself back up with control.</li></ol>',
            ],
            'notes' => [
                'de' => 'Zu schwer? Mach die Liegestütze auf den Knien oder mit den Händen auf einer Bank.',
                'en' => 'Too hard? Do the push-ups on your knees or with your hands on a bench.',
            ],
            'video' => 'https://www.youtube.com/watch?v=WDIpL0pjun0',
        ],
        'lunge' => [
            'title' => ['de' => 'Ausfallschritt', 'en' => 'Lunge'],
            'category' => 'strength',
            'difficulty' => 1,
            'primary' => 'legs',
            'muscles' => ['glutes', 'core'],
            'description' => [
                'de' => '<p>Der Ausfallschritt trainiert jedes Bein einzeln und verbessert Gleichgewicht und Stabilität.</p>',
                'en' => '<p>The lunge trains each leg on its own and improves balance and stability.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Aufrecht stehen, Hände an die Hüfte.</li><li>Mach einen großen Schritt nach vorn und senke das hintere Knie Richtung Boden.</li><li>Beide Knie sind etwa im rechten Winkel, das vordere Knie bleibt über dem Fuß.</li><li>Drück dich über die vordere Ferse zurück in den Stand und wechsle die Seite.</li></ol>',
                'en' => '<ol><li>Stand upright, hands on your hips.</li><li>Take a big step forward and lower your back knee towards the floor.</li><li>Both knees are at about a right angle, the front knee stays above the foot.</li><li>Push back to standing through your front heel and switch sides.</li></ol>',
            ],
            'notes' => [
                'de' => 'Die Wiederholungen gelten pro Seite.',
                'en' => 'The repetitions count per side.',
            ],
            'video' => 'https://www.youtube.com/watch?v=Z2n58m2i4jg',
        ],
        'row' => [
            'title' => ['de' => 'Einarmiges Kurzhantel-Rudern', 'en' => 'One-arm dumbbell row'],
            'category' => 'strength',
            'difficulty' => 2,
            'primary' => 'back',
            'muscles' => ['arms', 'shoulders'],
            'description' => [
                'de' => '<p>Rudern stärkt den oberen Rücken und hilft gegen einen runden Rücken vom vielen Sitzen.</p>',
                'en' => '<p>Rowing strengthens the upper back and helps against a rounded back from sitting a lot.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Stütz dich mit einer Hand und einem Knie auf einer Bank ab, der Rücken ist gerade.</li><li>Die andere Hand hält die Kurzhantel mit gestrecktem Arm.</li><li>Zieh die Hantel nah am Körper Richtung Hüfte und drück das Schulterblatt nach hinten.</li><li>Senke die Hantel langsam ab und wechsle nach dem Satz die Seite.</li></ol>',
                'en' => '<ol><li>Support yourself with one hand and one knee on a bench, back straight.</li><li>The other hand holds the dumbbell with a straight arm.</li><li>Pull the dumbbell close to your body towards your hip and squeeze your shoulder blade back.</li><li>Lower the dumbbell slowly and switch sides after the set.</li></ol>',
            ],
            'notes' => [
                'de' => 'Ohne Hantel geht auch eine gefüllte Wasserflasche.',
                'en' => 'Without a dumbbell, a filled water bottle works as well.',
            ],
            'video' => 'https://www.youtube.com/watch?v=PgpQ4-jHiq4',
        ],
        'plank' => [
            'title' => ['de' => 'Unterarmstütz', 'en' => 'Plank'],
            'category' => 'strength',
            'difficulty' => 1,
            'primary' => 'core',
            'muscles' => ['shoulders', 'glutes'],
            'description' => [
                'de' => '<p>Der Unterarmstütz stärkt die gesamte Rumpfmuskulatur und schützt so den Rücken.</p>',
                'en' => '<p>The plank strengthens your whole core and so protects your back.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Leg die Unterarme schulterbreit auf, die Ellbogen sind unter den Schultern.</li><li>Streck die Beine nach hinten und stütz dich auf die Zehen.</li><li>Halte den Körper wie ein Brett gerade: Hüfte nicht durchhängen lassen und nicht nach oben schieben.</li><li>Ruhig weiteratmen und die Spannung halten.</li></ol>',
                'en' => '<ol><li>Place your forearms shoulder-width apart, elbows below your shoulders.</li><li>Stretch your legs back and rest on your toes.</li><li>Keep your body straight like a board: don\'t let your hips sag or push them up.</li><li>Keep breathing calmly and hold the tension.</li></ol>',
            ],
            'notes' => [
                'de' => 'Spürst du es im unteren Rücken, mach eine Pause und spann den Bauch stärker an.',
                'en' => 'If you feel it in your lower back, take a break and tighten your abs more.',
            ],
            'video' => 'https://www.youtube.com/watch?v=pvIjsG5Svck',
        ],
        'bridge' => [
            'title' => ['de' => 'Hüftheben', 'en' => 'Glute bridge'],
            'category' => 'strength',
            'difficulty' => 1,
            'primary' => 'glutes',
            'muscles' => ['legs', 'core'],
            'description' => [
                'de' => '<p>Das Hüftheben kräftigt Gesäß und hintere Oberschenkel und ist schonend für die Knie.</p>',
                'en' => '<p>The glute bridge strengthens glutes and hamstrings and is easy on the knees.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Leg dich auf den Rücken, die Füße stehen hüftbreit auf, die Knie sind gebeugt.</li><li>Spann Bauch und Gesäß an und heb die Hüfte, bis Knie, Hüfte und Schultern eine Linie bilden.</li><li>Halte oben kurz und senke die Hüfte langsam wieder ab.</li></ol>',
                'en' => '<ol><li>Lie on your back, feet hip-width apart on the floor, knees bent.</li><li>Tighten abs and glutes and lift your hips until knees, hips and shoulders form a line.</li><li>Hold briefly at the top and lower your hips slowly.</li></ol>',
            ],
            'notes' => ['de' => '', 'en' => ''],
            'video' => '',
        ],
        'press' => [
            'title' => ['de' => 'Schulterdrücken mit Kurzhanteln', 'en' => 'Dumbbell shoulder press'],
            'category' => 'strength',
            'difficulty' => 2,
            'primary' => 'shoulders',
            'muscles' => ['arms', 'core'],
            'description' => [
                'de' => '<p>Schulterdrücken baut kräftige Schultern auf und stärkt die Arme.</p>',
                'en' => '<p>The shoulder press builds strong shoulders and strengthens your arms.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Stell dich hüftbreit hin oder setz dich aufrecht auf eine Bank.</li><li>Halte die Kurzhanteln auf Schulterhöhe, die Handflächen zeigen nach vorn.</li><li>Drück die Hanteln nach oben, bis die Arme fast gestreckt sind.</li><li>Senke sie langsam zurück auf Schulterhöhe.</li></ol>',
                'en' => '<ol><li>Stand hip-width apart or sit upright on a bench.</li><li>Hold the dumbbells at shoulder height, palms facing forward.</li><li>Press the dumbbells up until your arms are almost straight.</li><li>Lower them slowly back to shoulder height.</li></ol>',
            ],
            'notes' => [
                'de' => 'Nicht ins Hohlkreuz fallen, der Bauch bleibt angespannt.',
                'en' => 'Don\'t arch your back, keep your abs tight.',
            ],
            'video' => 'https://www.youtube.com/watch?v=aI2hGzsAMXs',
        ],
        'jumpingjack' => [
            'title' => ['de' => 'Hampelmann', 'en' => 'Jumping jacks'],
            'category' => 'cardio',
            'difficulty' => 1,
            'primary' => 'legs',
            'muscles' => ['shoulders'],
            'description' => [
                'de' => '<p>Der Hampelmann bringt den Kreislauf in Schwung und eignet sich gut zum Aufwärmen.</p>',
                'en' => '<p>Jumping jacks get your heart going and are great for warming up.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Steh aufrecht, die Arme hängen locker neben dem Körper.</li><li>Spring mit gegrätschten Beinen ab und führ die Arme über den Kopf.</li><li>Spring zurück in die Ausgangsposition und wiederhole das zügig.</li></ol>',
                'en' => '<ol><li>Stand upright, arms loosely by your sides.</li><li>Jump your legs apart and bring your arms over your head.</li><li>Jump back to the start and repeat quickly.</li></ol>',
            ],
            'notes' => [
                'de' => 'Ohne Springen: abwechselnd ein Bein zur Seite setzen.',
                'en' => 'Without jumping: step one leg to the side at a time.',
            ],
            'video' => '',
        ],
        'climber' => [
            'title' => ['de' => 'Bergsteiger', 'en' => 'Mountain climber'],
            'category' => 'cardio',
            'difficulty' => 2,
            'primary' => 'core',
            'muscles' => ['shoulders', 'legs'],
            'description' => [
                'de' => '<p>Der Bergsteiger verbindet Ausdauer und Rumpfkraft und treibt den Puls schnell nach oben.</p>',
                'en' => '<p>The mountain climber combines endurance and core strength and raises your pulse quickly.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Geh in den Liegestütz mit gestreckten Armen.</li><li>Zieh abwechselnd ein Knie Richtung Brust.</li><li>Wechsle zügig die Beine, die Hüfte bleibt dabei tief.</li></ol>',
                'en' => '<ol><li>Get into a push-up position with straight arms.</li><li>Pull one knee towards your chest, then the other.</li><li>Switch legs quickly while keeping your hips low.</li></ol>',
            ],
            'notes' => ['de' => '', 'en' => ''],
            'video' => 'https://www.youtube.com/watch?v=kLh-uczlPLg',
        ],
        'burpee' => [
            'title' => ['de' => 'Burpee', 'en' => 'Burpee'],
            'category' => 'cardio',
            'difficulty' => 3,
            'primary' => 'legs',
            'muscles' => ['chest', 'core', 'shoulders'],
            'description' => [
                'de' => '<p>Der Burpee ist eine anstrengende Ganzkörperübung aus Hocke, Liegestütz und Strecksprung.</p>',
                'en' => '<p>The burpee is a demanding full-body exercise made of a squat, a push-up position and a jump.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Geh in die Hocke und setz die Hände vor den Füßen auf.</li><li>Spring mit den Füßen nach hinten in den Liegestütz.</li><li>Spring mit den Füßen zurück zu den Händen.</li><li>Streck dich und spring mit den Armen über dem Kopf nach oben.</li></ol>',
                'en' => '<ol><li>Squat down and place your hands in front of your feet.</li><li>Jump your feet back into a push-up position.</li><li>Jump your feet back towards your hands.</li><li>Stand up and jump with your arms over your head.</li></ol>',
            ],
            'notes' => [
                'de' => 'Leichter: Füße einzeln nach hinten setzen und ohne Sprung aufstehen.',
                'en' => 'Easier: step your feet back one at a time and stand up without jumping.',
            ],
            'video' => '',
        ],
        'catcow' => [
            'title' => ['de' => 'Katze-Kuh', 'en' => 'Cat-cow'],
            'category' => 'mobility',
            'difficulty' => 1,
            'primary' => 'back',
            'muscles' => ['core'],
            'description' => [
                'de' => '<p>Diese sanfte Übung macht die Wirbelsäule beweglich und löst Verspannungen im Rücken.</p>',
                'en' => '<p>This gentle exercise mobilises your spine and releases tension in your back.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Geh in den Vierfüßlerstand, Hände unter den Schultern, Knie unter der Hüfte.</li><li>Atme ein und lass den Bauch sinken, der Blick geht leicht nach oben.</li><li>Atme aus und mach den Rücken rund wie eine Katze, das Kinn geht zur Brust.</li><li>Wechsle ruhig im Atemrhythmus.</li></ol>',
                'en' => '<ol><li>Get on all fours, hands below shoulders, knees below hips.</li><li>Breathe in and let your belly sink, looking slightly up.</li><li>Breathe out and round your back like a cat, chin towards your chest.</li><li>Switch calmly with your breath.</li></ol>',
            ],
            'notes' => ['de' => '', 'en' => ''],
            'video' => '',
        ],
        'hipstretch' => [
            'title' => ['de' => 'Hüftbeuger-Dehnung', 'en' => 'Hip flexor stretch'],
            'category' => 'mobility',
            'difficulty' => 1,
            'primary' => 'legs',
            'muscles' => ['glutes'],
            'description' => [
                'de' => '<p>Dehnt die Hüftbeuger, die durch langes Sitzen oft verkürzt sind.</p>',
                'en' => '<p>Stretches the hip flexors, which are often tight from sitting for long periods.</p>',
            ],
            'instructions' => [
                'de' => '<ol><li>Knie dich in einen tiefen Ausfallschritt, das hintere Knie liegt auf einer Matte.</li><li>Richte den Oberkörper auf und schieb die Hüfte langsam nach vorn.</li><li>Halte die Dehnung, ohne zu wippen, und wechsle dann die Seite.</li></ol>',
                'en' => '<ol><li>Kneel in a deep lunge, back knee resting on a mat.</li><li>Straighten your upper body and slowly push your hips forward.</li><li>Hold the stretch without bouncing, then switch sides.</li></ol>',
            ],
            'notes' => [
                'de' => 'Die Zeit gilt pro Seite.',
                'en' => 'The time counts per side.',
            ],
            'video' => '',
        ],
    ],

    // Rows of a workout: exercise, sets, reps from, reps to, weight, duration in seconds, rest in seconds.
    'workouts' => [
        'fullA' => [
            'title' => ['de' => 'Ganzkörper A', 'en' => 'Full body A'],
            'description' => [
                'de' => '<p>Grundübungen für den ganzen Körper. Ideal für den Einstieg.</p>',
                'en' => '<p>Basic exercises for the whole body. Ideal for getting started.</p>',
            ],
            'duration' => 30,
            'difficulty' => 1,
            'items' => [
                ['squat', 3, 10, 12, '', null, 60],
                ['pushup', 3, 8, 10, '', null, 60],
                ['row', 3, 10, 12, ['de' => 'z. B. 8 kg', 'en' => 'e.g. 8 kg'], null, 60],
                ['plank', 3, null, null, '', 30, 45],
            ],
        ],
        'fullB' => [
            'title' => ['de' => 'Ganzkörper B', 'en' => 'Full body B'],
            'description' => [
                'de' => '<p>Die zweite Ganzkörpereinheit mit Fokus auf Beine, Schultern und Gesäß.</p>',
                'en' => '<p>The second full-body session with a focus on legs, shoulders and glutes.</p>',
            ],
            'duration' => 30,
            'difficulty' => 1,
            'items' => [
                ['lunge', 3, 10, 10, '', null, 60],
                ['press', 3, 10, 12, ['de' => 'z. B. 6 kg', 'en' => 'e.g. 6 kg'], null, 60],
                ['bridge', 3, 12, 15, '', null, 45],
                ['plank', 3, null, null, '', 40, 45],
            ],
        ],
        'cardio' => [
            'title' => ['de' => 'Cardio-Zirkel', 'en' => 'Cardio circuit'],
            'description' => [
                'de' => '<p>Kurz und knackig: vier Übungen hintereinander, danach eine Runde Pause.</p>',
                'en' => '<p>Short and intense: four exercises in a row, then a break.</p>',
            ],
            'duration' => 20,
            'difficulty' => 2,
            'items' => [
                ['jumpingjack', 3, null, null, '', 45, 15],
                ['climber', 3, null, null, '', 30, 15],
                ['squat', 3, 15, 15, '', null, 15],
                ['burpee', 3, 8, 10, '', null, 60],
            ],
        ],
        'mobility' => [
            'title' => ['de' => 'Mobilität & Entspannung', 'en' => 'Mobility & cool-down'],
            'description' => [
                'de' => '<p>Ruhige Übungen für einen beweglichen Rücken und lockere Hüften.</p>',
                'en' => '<p>Calm exercises for a mobile back and loose hips.</p>',
            ],
            'duration' => 15,
            'difficulty' => 1,
            'items' => [
                ['catcow', 2, 10, 10, '', null, 30],
                ['hipstretch', 2, null, null, '', 45, 15],
                ['bridge', 2, 12, 12, '', null, 30],
            ],
        ],
        'strength' => [
            'title' => ['de' => 'Kraft intensiv', 'en' => 'Strength intense'],
            'description' => [
                'de' => '<p>Mehr Sätze, mehr Gewicht: die fordernde Einheit für Fortgeschrittene.</p>',
                'en' => '<p>More sets, more weight: the demanding session for advanced trainees.</p>',
            ],
            'duration' => 45,
            'difficulty' => 3,
            'items' => [
                ['squat', 4, 12, 15, '', null, 90],
                ['pushup', 4, 10, 15, '', null, 90],
                ['row', 4, 8, 10, ['de' => 'z. B. 12 kg', 'en' => 'e.g. 12 kg'], null, 90],
                ['press', 4, 8, 10, ['de' => 'z. B. 8 kg', 'en' => 'e.g. 8 kg'], null, 90],
                ['burpee', 3, 10, 10, '', null, 60],
                ['plank', 3, null, null, '', 60, 45],
            ],
        ],
    ],

    'programs' => [
        'starter' => [
            'title' => ['de' => 'Fit in 4 Wochen', 'en' => 'Fit in 4 weeks'],
            'teaser' => [
                'de' => 'Der sanfte Einstieg: zwei Ganzkörper-Trainings pro Woche, dazu Mobilität und ab Woche 3 etwas Cardio.',
                'en' => 'The gentle start: two full-body workouts per week, plus mobility and some cardio from week 3.',
            ],
            'description' => [
                'de' => '<p>Dieses Programm ist für alle gedacht, die (wieder) mit dem Training anfangen. Du brauchst nur eine Matte und eine Kurzhantel oder Wasserflasche.</p><ul><li>2 feste Trainings pro Woche</li><li>freiwillige Mobilitätseinheit</li><li>ab Woche 3 ein kurzer Cardio-Zirkel</li></ul>',
                'en' => '<p>This program is for everyone who is starting (again) with training. You only need a mat and a dumbbell or water bottle.</p><ul><li>2 fixed workouts per week</li><li>optional mobility session</li><li>a short cardio circuit from week 3</li></ul>',
            ],
            'goal' => ['de' => 'Grundfitness', 'en' => 'General fitness'],
            'difficulty' => 1,
            'price' => null,
            'image' => 'program-starter',
            'phases' => $weeks(
                4,
                static fn (int $week): array => array_merge(
                    [
                        $session('fullA', $trainingA, 1),
                        $session('fullB', $trainingB, 3),
                    ],
                    $week >= 3 ? [$session('cardio', $cardio, 5)] : [],
                    [$session('mobility', $mobility, 6, true)]
                ),
                static fn (int $week): array => $week === 1
                    ? ['de' => 'Ankommen und die Übungen kennenlernen.', 'en' => 'Getting started and learning the exercises.']
                    : ($week >= 3
                        ? ['de' => 'Jetzt kommt ein kurzer Cardio-Zirkel dazu.', 'en' => 'Now a short cardio circuit is added.']
                        : ['de' => 'Gleiche Übungen, etwas mehr Sicherheit.', 'en' => 'Same exercises, a bit more confidence.'])
            ),
        ],
        'strength' => [
            'title' => ['de' => 'Kraft-Aufbau 6 Wochen', 'en' => 'Strength builder 6 weeks'],
            'teaser' => [
                'de' => 'Sechs Wochen strukturierter Kraftaufbau mit drei Einheiten pro Woche, ab Woche 3 mit der intensiven Einheit.',
                'en' => 'Six weeks of structured strength training with three sessions per week, the intense session from week 3.',
            ],
            'description' => [
                'de' => '<p>Für alle, die schon Grundübungen beherrschen und gezielt stärker werden wollen.</p><ul><li>3 Trainings pro Woche</li><li>ab Woche 3 die Einheit „Kraft intensiv“</li><li>freiwillige Mobilität am Wochenende</li></ul><p>Dieses Beispielprogramm ist kostenpflichtig, damit du den Bestellablauf ausprobieren kannst.</p>',
                'en' => '<p>For everyone who already masters the basic exercises and wants to get stronger.</p><ul><li>3 workouts per week</li><li>the session "Strength intense" from week 3</li><li>optional mobility at the weekend</li></ul><p>This sample program is paid, so you can try the ordering process.</p>',
            ],
            'goal' => ['de' => 'Muskelaufbau', 'en' => 'Muscle gain'],
            'difficulty' => 2,
            'price' => '19.90',
            'image' => 'program-strength',
            'phases' => $weeks(
                6,
                static fn (int $week): array => [
                    $session('fullA', $trainingA, 1),
                    $session('fullB', $trainingB, 3),
                    $week >= 3 ? $session('strength', $trainingC, 5) : $session('cardio', $trainingC, 5),
                    $session('mobility', $mobility, 7, true),
                ],
                static fn (int $week): array => $week <= 2
                    ? ['de' => 'Grundlagen festigen.', 'en' => 'Building the basics.']
                    : ['de' => 'Steigern: Training C wird zur intensiven Krafteinheit.', 'en' => 'Increasing: workout C becomes the intense strength session.']
            ),
        ],
        'cardio' => [
            'title' => ['de' => 'Cardio-Kick 2 Wochen', 'en' => 'Cardio kick 2 weeks'],
            'teaser' => [
                'de' => 'Zwei Wochen, drei kurze Cardio-Zirkel pro Woche: mehr Puste in kurzer Zeit.',
                'en' => 'Two weeks, three short cardio circuits per week: more stamina in a short time.',
            ],
            'description' => [
                'de' => '<p>Kurze, intensive Einheiten für zwischendurch. Jede Einheit dauert nur etwa 20 Minuten.</p>',
                'en' => '<p>Short, intense sessions for in between. Each session only takes about 20 minutes.</p>',
            ],
            'goal' => ['de' => 'Ausdauer', 'en' => 'Endurance'],
            'difficulty' => 2,
            'price' => null,
            'image' => 'program-cardio',
            'phases' => $weeks(
                2,
                static fn (int $week): array => [
                    $session('cardio', ['de' => 'Cardio 1', 'en' => 'Cardio 1'], 2),
                    $session('cardio', ['de' => 'Cardio 2', 'en' => 'Cardio 2'], 4),
                    $session('cardio', ['de' => 'Cardio 3', 'en' => 'Cardio 3'], 6),
                    $session('mobility', $mobility, 7, true),
                ],
                static fn (int $week): array => $week === 1
                    ? ['de' => 'Den Rhythmus finden.', 'en' => 'Finding the rhythm.']
                    : ['de' => 'Versuch, die Pausen kurz zu halten.', 'en' => 'Try to keep the breaks short.']
            ),
        ],
    ],
];
