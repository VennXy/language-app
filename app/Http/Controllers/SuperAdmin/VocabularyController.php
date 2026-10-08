<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Vocabulary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class VocabularyController extends Controller
{
    /**
     * Store a newly created vocabulary in storage.
     */
    public function store(Request $request, $lessonId)
    {
        // Validation
        $request->validate([
            'question_type'  => 'required|string',
            'question'       => 'required|string',
            'correct_answer' => 'required|string',
            'options'        => 'nullable|string',

            // Image Section
            'option_images'   => 'nullable|array',
            'option_images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',

            // Audio
            'audio' => 'nullable|file|mimes:mp3,wav,aac|max:10240',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Convert Options
        |--------------------------------------------------------------------------
        |
        | Admin can enter:
        |
        | スポーツ
        | まんが
        | ゲーム
        |
        | or:
        |
        | スポーツ,まんが,ゲーム
        |
        */

        $options = $this->parseOptions($request->options);


        /*
        |--------------------------------------------------------------------------
        | Create Vocabulary
        |--------------------------------------------------------------------------
        */

        $vocabulary = new Vocabulary();

        $vocabulary->lesson_id = $lessonId;
        $vocabulary->question_type = $request->question_type;
        $vocabulary->question = $request->question;
        $vocabulary->correct_answer = $request->correct_answer;

        // Keep existing database format
        $vocabulary->options = implode(',', $options);


        /*
        |--------------------------------------------------------------------------
        | Audio Upload
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('audio')) {

            $vocabulary->audio = $request
                ->file('audio')
                ->store('audios', 'public');
        }


        /*
        |--------------------------------------------------------------------------
        | Image Section
        |--------------------------------------------------------------------------
        |
        | Each option gets one image.
        |
        | Example:
        |
        | options:
        | 0 = スポーツ
        | 1 = まんが
        | 2 = ゲーム
        |
        | option_images:
        | 0 = sports.png
        | 1 = manga.png
        | 2 = game.png
        |
        | Saved into image_url as JSON.
        |
        */

        $imageUrls = [];

        if ($request->hasFile('option_images')) {

            foreach ($request->file('option_images') as $index => $file) {

                if (!$file) {
                    continue;
                }

                // Make sure an option exists for this image
                if (!isset($options[$index])) {
                    continue;
                }

                $path = $file->store(
                    'vocabularies',
                    'public'
                );

                $imageUrls[$options[$index]] = $path;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Save image_url
        |--------------------------------------------------------------------------
        */

        if (!empty($imageUrls)) {

            $vocabulary->image_url = json_encode(
                $imageUrls,
                JSON_UNESCAPED_UNICODE
            );

        } else {

            $vocabulary->image_url = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        $vocabulary->save();


        return redirect()
            ->back()
            ->with(
                'success',
                'Training question added successfully!'
            );
    }


    /**
     * Update the specified vocabulary in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'question_type'  => 'required|string',
            'question'       => 'required|string',
            'correct_answer' => 'required|string',
            'options'        => 'nullable|string',

            // Image Section
            'option_images'   => 'nullable|array',
            'option_images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',

            // Audio
            'audio' => 'nullable|file|mimes:mp3,wav,aac|max:10240',
        ]);


        $vocabulary = Vocabulary::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | Convert Options
        |--------------------------------------------------------------------------
        */

        $options = $this->parseOptions($request->options);


        /*
        |--------------------------------------------------------------------------
        | Existing Image Data
        |--------------------------------------------------------------------------
        */

        $oldImages = [];

        if (!empty($vocabulary->image_url)) {

            $decodedImages = json_decode(
                $vocabulary->image_url,
                true
            );

            if (is_array($decodedImages)) {
                $oldImages = $decodedImages;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Keep Images That Still Match Existing Options
        |--------------------------------------------------------------------------
        */

        $imageUrls = [];

        foreach ($options as $option) {

            if (isset($oldImages[$option])) {

                $imageUrls[$option] = $oldImages[$option];
            }
        }

        $oldAudio = $vocabulary->audio;
        $oldImagePaths = array_values(array_filter($oldImages, 'is_string'));


        /*
        |--------------------------------------------------------------------------
        | Upload New / Replacement Images
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('option_images')) {

            foreach ($request->file('option_images') as $index => $file) {

                if (!$file) {
                    continue;
                }

                if (!isset($options[$index])) {
                    continue;
                }

                $option = $options[$index];


                /*
                |--------------------------------------------------------------------------
                | Upload new image
                |--------------------------------------------------------------------------
                */

                $path = $file->store(
                    'vocabularies',
                    'public'
                );


                $imageUrls[$option] = $path;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Update Basic Data
        |--------------------------------------------------------------------------
        */

        $vocabulary->question_type = $request->question_type;

        $vocabulary->question = $request->question;

        $vocabulary->correct_answer =
            $request->correct_answer;

        $vocabulary->options =
            implode(',', $options);


        /*
        |--------------------------------------------------------------------------
        | Update Audio
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('audio')) {
            $vocabulary->audio =
                $request
                    ->file('audio')
                    ->store('audios', 'public');
        }


        /*
        |--------------------------------------------------------------------------
        | Update Image URL
        |--------------------------------------------------------------------------
        */

        if (!empty($imageUrls)) {

            $vocabulary->image_url =
                json_encode(
                    $imageUrls,
                    JSON_UNESCAPED_UNICODE
                );

        } else {

            $vocabulary->image_url = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        $vocabulary->save();

        if ($oldAudio && $oldAudio !== $vocabulary->audio) {
            $this->deleteAudioIfUnused($oldAudio);
        }
        foreach (array_diff($oldImagePaths, $this->imagePaths($vocabulary->image_url)) as $path) {
            $this->deleteImageIfUnused($path);
        }


        return redirect()
            ->back()
            ->with(
                'success',
                'Question updated successfully!'
            );
    }


    /**
     * Remove the specified vocabulary from storage.
     */
    public function destroy($id)
    {
        $vocabulary = Vocabulary::findOrFail($id);
        $imagePaths = $this->imagePaths($vocabulary->image_url);
        $audio = $vocabulary->audio;
        $vocabulary->delete();

        if ($audio) {
            $this->deleteAudioIfUnused($audio);
        }
        foreach ($imagePaths as $path) {
            $this->deleteImageIfUnused($path);
        }


        return redirect()
            ->back()
            ->with(
                'success',
                'Vocabulary deleted successfully!'
            );
    }


    /**
     * Import vocabulary data.
     */
    public function importData(Request $request, $lessonId)
    {
        $request->validate([
            'import_file' => 'required|mimes:json,csv,txt'
        ]);


        $file = $request->file('import_file');

        $path = $file->getRealPath();


        /*
        |--------------------------------------------------------------------------
        | JSON Import
        |--------------------------------------------------------------------------
        */

        if (
            $file->getClientOriginalExtension()
            === 'json'
        ) {

            $data = json_decode(
                file_get_contents($path),
                true
            );


            foreach ($data as $row) {

                Vocabulary::create([

                    'lesson_id' =>
                        $lessonId,

                    'question' =>
                        $row['question'] ?? null,

                    'correct_answer' =>
                        $row['correct_answer'] ?? null,

                    'options' =>
                        $row['options'] ?? null,

                    'image_url' =>
                        $row['image_url'] ?? null,

                    'question_type' =>
                        $row['question_type']
                        ?? 'word_selection',

                ]);
            }
        }


        return redirect()
            ->back()
            ->with(
                'success',
                'Vocabularies imported successfully!'
            );
    }

    /**
     * Parse image-option JSON from the lesson form while retaining the
     * comma/newline format used by the other existing question types.
     */
    private function parseOptions(?string $value): array
    {
        $decoded = json_decode($value ?? '', true);

        if (is_array($decoded) && array_is_list($decoded)) {
            $options = $decoded;
        } else {
            $options = preg_split('/[\r\n,]+/', $value ?? '');
        }

        return collect($options)
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->values()
            ->all();
    }

    private function imagePaths(?string $imageUrl): array
    {
        $images = json_decode($imageUrl ?? '', true);

        return is_array($images) ? array_values(array_filter($images, 'is_string')) : [];
    }

    private function deleteImageIfUnused(string $path): void
    {
        $usedByQuestion = DB::table('vocabularies')
            ->pluck('image_url')
            ->contains(fn (?string $imageUrl) => in_array($path, $this->imagePaths($imageUrl), true));

        $usedByLearningItem = Schema::hasTable('lesson_vocabulary_items')
            && Schema::hasColumn('lesson_vocabulary_items', 'image_path')
            && DB::table('lesson_vocabulary_items')->where('image_path', $path)->exists();

        if (!$usedByQuestion && !$usedByLearningItem) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteAudioIfUnused(string $path): void
    {
        $usedByQuestion = DB::table('vocabularies')->where('audio', $path)->exists();
        if (!$usedByQuestion && Schema::hasColumn('vocabularies', 'audio_path')) {
            $usedByQuestion = DB::table('vocabularies')->where('audio_path', $path)->exists();
        }

        $usedByLearningItem = Schema::hasTable('lesson_vocabulary_items')
            && Schema::hasColumn('lesson_vocabulary_items', 'audio_path')
            && DB::table('lesson_vocabulary_items')->where('audio_path', $path)->exists();

        if (!$usedByQuestion && !$usedByLearningItem) {
            Storage::disk('public')->delete($path);
        }
    }
}
