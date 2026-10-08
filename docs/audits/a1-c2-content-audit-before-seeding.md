# A1–C2 Content Audit (Before Corrections and Seeding)

Snapshot date: 2026-09-27 (local application database).

## Runtime schema and support

The live `vocabularies` table contains `id`, `lesson_id`, `audio`, `question_type`, `question`, `options`, `correct_answer`, and `image_url` (plus timestamps). No schema changes were made.
Supported training types: `word_selection` (tokens assembled and checked by server), `phrase_ordering` (Japanese token order checked after whitespace removal), `translation_tiles` (English tiles shuffled and checked by server), and `image_selection` (option image map and chosen option checked by server). The database/admin include `audio`, but the Training view has no audio renderer; treat it as unsupported in training.
Schema drift found: repository migrations declare `audio_path` and do not create `image_url`, while the live database/model use `audio` and `image_url`. The audit did not alter migrations.

## Lesson structure and counts before content insertion

| Level | Lesson ID | Actual title | Expected title | Sort order | Existing questions |
|---|---:|---|---|---:|---:|
| A1 | 3 | Lesson-1 | Greetings & Self Introduction | 1 | 9 |
| A1 | 4 | Lesson-2 | Numbers, Time & Dates | 1 | 9 |
| A1 | 27 | Daily Life | Daily Life | 2 | 5 |
| A1 | 28 | Basic Places & Activities | Places & Activities | 3 | 5 |
| A2 | 29 | Daily Routines | Daily Routines | 1 | 5 |
| A2 | 30 | Shopping & Food | Shopping & Food | 2 | 5 |
| A2 | 31 | Travel & Directions | Travel & Directions | 3 | 5 |
| A2 | 32 | Experiences & Plans | Experiences & Plans | 4 | 5 |
| B1 | 33 | Opinions & Reasons | Opinions & Reasons | 1 | 5 |
| B1 | 34 | Experiences & Stories | Experiences & Stories | 2 | 5 |
| B1 | 35 | Social Conversations | Social Conversations | 3 | 5 |
| B1 | 36 | Everyday Reading | Everyday Reading | 4 | 5 |
| B2 | 37 | Formal & Informal Japanese | Formal & Informal Japanese | 1 | 5 |
| B2 | 38 | Work & Society | Work & Society | 2 | 5 |
| B2 | 39 | News & Information | News & Information | 3 | 5 |
| B2 | 40 | Complex Conversations | Complex Conversations | 4 | 5 |
| C1 | 41 | Advanced Formal Japanese | Advanced Formal Japanese | 1 | 5 |
| C1 | 42 | Abstract Topics | Abstract Topics | 2 | 5 |
| C1 | 43 | Advanced Reading | Advanced Reading | 3 | 5 |
| C1 | 44 | Nuanced Expressions | Nuanced Expressions | 4 | 5 |
| C2 | 45 | Advanced Vocabulary | Advanced Vocabulary | 1 | 5 |
| C2 | 46 | Complex Grammar | Complex Grammar | 2 | 5 |
| C2 | 47 | Advanced Reading & Interpretation | Advanced Reading & Interpretation | 3 | 5 |
| C2 | 48 | Nuance, Idioms & Formal Context | Nuance, Idioms & Formal Context | 4 | 5 |

A1 has four lesson rows, but the first two titles are generic (`Lesson-1`, `Lesson-2`), their descriptions do not match their topics, and sort orders are `1,1,2,3`. Lesson 4 is titled `Basic Places & Activities`, not the requested `Places & Activities`. A1 Lesson 2 includes four older object/vocabulary questions that are valid but off the Numbers, Time & Dates topic; they are retained and flagged for topic coverage.

## Per-question audit

Status meanings: PASS = structurally valid and compatible; WARNING = usable but has a content-quality/topic concern; ERROR = not correctly usable in the current Training UI.

| Level | Lesson (ID) | Q ID | Type | Japanese question / prompt | Options | Correct answer | Image reference(s) | Structure | Japanese quality | English / translation | Options valid | Answer matches options | Type matches content | Duplicate | Broken image | Missing required fields | Status |
|---|---|---:|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| A1 | Lesson-1 (#3) | 14 | word_selection | みずです | water,please,it is,hat | it is water | — | PASS | PASS | PASS: it is water matches みずです | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-1 (#3) | 18 | audio | Listen to voice | みず,すし,お茶,ごはん | みず | — | ERROR | N/A | N/A | PASS | PASS | ERROR | PASS | N/A | audio file | ERROR |
| A1 | Lesson-1 (#3) | 20 | phrase_ordering | ဝါကျကို အစဉ်လိုက် ပြန်လည်စီပါ | これは,みず,です,すし | これはみずです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-1 (#3) | 23 | image_selection | Which one of these "game"? | ゲーム,みみ,うう | ゲーム | ゲーム → vocabularies/Y9UmVAhOt0x3fluOuALNm93sEidkQ5Vv3fasCUP0.png<br>みみ → vocabularies/DoJVUfjoGTsqLRZXVkFRuVhjZ4Qa5sMAgggOjthB.png<br>うう → vocabularies/DPBnpSlSHPyPCbj1eTsX4Fy98aeaK6jYqirVNucR.png | PASS | N/A | WARNING: malformed English prompt; image labels do not match the editor/terminal assets | WARNING | PASS | WARNING | PASS | PASS | None | WARNING |
| A1 | Lesson-2 (#4) | 24 | word_selection | これ は 本 です | これ,は,本,です | これ は 本 です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-2 (#4) | 25 | word_selection | わたし は 学生 です | わたし,は,学生,です | わたし は 学生 です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-2 (#4) | 26 | word_selection | 水 を ください | 水,を,ください | 水 を ください | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-2 (#4) | 27 | word_selection | ねこ は かわいい です | ねこ,は,かわいい,です | ねこ は かわいい です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-1 (#3) | 148 | word_selection | 田中 さん おはよう ございます | 田中,さん,おはよう,ございます | 田中 さん おはよう ございます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-1 (#3) | 149 | word_selection | はじめまして わたし は 田中 です | はじめまして,わたし,は,田中,です | はじめまして わたし は 田中 です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-1 (#3) | 150 | word_selection | わたし は 大阪 から 来ました | わたし,は,大阪,から,来ました | わたし は 大阪 から 来ました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-1 (#3) | 151 | word_selection | どうぞ よろしく お願いします | どうぞ,よろしく,お願いします | どうぞ よろしく お願いします | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-1 (#3) | 152 | word_selection | こちら は 友だち の 山田 さん です | こちら,は,友だち,の,山田,さん,です | こちら は 友だち の 山田 さん です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-2 (#4) | 153 | word_selection | 今 何時 ですか | 今,何時,ですか | 今 何時 ですか | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-2 (#4) | 154 | word_selection | 毎朝 七時 に 起きます | 毎朝,七時,に,起きます | 毎朝 七時 に 起きます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-2 (#4) | 155 | word_selection | 今日は 五月 三日 です | 今日は,五月,三日,です | 今日は 五月 三日 です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-2 (#4) | 156 | word_selection | 日曜日 に 友だち と 映画 を 見ます | 日曜日,に,友だち,と,映画,を,見ます | 日曜日 に 友だち と 映画 を 見ます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Lesson-2 (#4) | 157 | word_selection | 二時 から 三時 まで 勉強します | 二時,から,三時,まで,勉強します | 二時 から 三時 まで 勉強します | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Daily Life (#27) | 158 | word_selection | 朝ごはん を 食べて 学校 へ 行きます | 朝ごはん,を,食べて,学校,へ,行きます | 朝ごはん を 食べて 学校 へ 行きます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Daily Life (#27) | 159 | word_selection | 図書館 で 日本語 を 勉強します | 図書館,で,日本語,を,勉強します | 図書館 で 日本語 を 勉強します | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Daily Life (#27) | 160 | word_selection | 昨日 は 家 で 本 を 読みました | 昨日,は,家,で,本,を,読みました | 昨日 は 家 で 本 を 読みました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Daily Life (#27) | 161 | word_selection | 毎晩 十一時 に 寝ます | 毎晩,十一時,に,寝ます | 毎晩 十一時 に 寝ます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Daily Life (#27) | 162 | word_selection | 週末 に 部屋 を そうじします | 週末,に,部屋,を,そうじします | 週末 に 部屋 を そうじします | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Basic Places & Activities (#28) | 163 | word_selection | 駅 の 前 に 銀行 が あります | 駅,の,前,に,銀行,が,あります | 駅 の 前 に 銀行 が あります | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Basic Places & Activities (#28) | 164 | word_selection | 公園 で 友だち と サッカー を します | 公園,で,友だち,と,サッカー,を,します | 公園 で 友だち と サッカー を します | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Basic Places & Activities (#28) | 165 | word_selection | スーパー で 牛乳 と パン を 買います | スーパー,で,牛乳,と,パン,を,買います | スーパー で 牛乳 と パン を 買います | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Basic Places & Activities (#28) | 166 | word_selection | 学校 まで バス で 行きます | 学校,まで,バス,で,行きます | 学校 まで バス で 行きます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A1 | Basic Places & Activities (#28) | 167 | word_selection | 右 に 曲がって ください | 右,に,曲がって,ください | 右 に 曲がって ください | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Daily Routines (#29) | 168 | word_selection | 毎朝 シャワー を 浴びてから 朝ごはん を 食べます | 毎朝,シャワー,を,浴びてから,朝ごはん,を,食べます | 毎朝 シャワー を 浴びてから 朝ごはん を 食べます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Daily Routines (#29) | 169 | word_selection | 昨日 は 雨 が 降っていたので 家 で 映画 を 見ました | 昨日,は,雨,が,降っていたので,家,で,映画,を,見ました | 昨日 は 雨 が 降っていたので 家 で 映画 を 見ました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Daily Routines (#29) | 170 | word_selection | 明日 は 早く 起きなければ ならないので 今夜 は 早く 寝ます | 明日,は,早く,起きなければ,ならないので,今夜,は,早く,寝ます | 明日 は 早く 起きなければ ならないので 今夜 は 早く 寝ます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Daily Routines (#29) | 171 | word_selection | 仕事 が 終わったあとで 友だち と 食事 を しました | 仕事,が,終わったあとで,友だち,と,食事,を,しました | 仕事 が 終わったあとで 友だち と 食事 を しました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Daily Routines (#29) | 172 | word_selection | 週 に 三回 泳ぐように しています | 週,に,三回,泳ぐように,しています | 週 に 三回 泳ぐように しています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Shopping & Food (#30) | 173 | word_selection | この 靴 は あの 靴 より 少し 安いです | この,靴,は,あの,靴,より,少し,安いです | この 靴 は あの 靴 より 少し 安いです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Shopping & Food (#30) | 174 | word_selection | すみません 試着しても いいですか | すみません,試着しても,いいですか | すみません 試着しても いいですか | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Shopping & Food (#30) | 175 | word_selection | コーヒー を 二つ お願いします | コーヒー,を,二つ,お願いします | コーヒー を 二つ お願いします | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Shopping & Food (#30) | 176 | word_selection | 辛くない 料理 を おすすめして いただけますか | 辛くない,料理,を,おすすめして,いただけますか | 辛くない 料理 を おすすめして いただけますか | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Shopping & Food (#30) | 177 | word_selection | 合計 で いくら に なりますか | 合計,で,いくら,に,なりますか | 合計 で いくら に なりますか | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Travel & Directions (#31) | 178 | word_selection | 次 の 電車 は 何番線 から 出ますか | 次,の,電車,は,何番線,から,出ますか | 次 の 電車 は 何番線 から 出ますか | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Travel & Directions (#31) | 179 | word_selection | 駅 に 着いたら 右 へ 曲がって ください | 駅,に,着いたら,右,へ,曲がって,ください | 駅 に 着いたら 右 へ 曲がって ください | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Travel & Directions (#31) | 180 | word_selection | 道 に 迷ったので 交番 で 聞きました | 道,に,迷ったので,交番,で,聞きました | 道 に 迷ったので 交番 で 聞きました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Travel & Directions (#31) | 181 | word_selection | ホテル を 予約して おいたほうが いいです | ホテル,を,予約して,おいたほうが,いいです | ホテル を 予約して おいたほうが いいです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Travel & Directions (#31) | 182 | word_selection | 雨 が 降りそうなので 傘 を 持って 行きます | 雨,が,降りそうなので,傘,を,持って,行きます | 雨 が 降りそうなので 傘 を 持って 行きます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Experiences & Plans (#32) | 183 | word_selection | 北海道 へ 行ったこと が あります | 北海道,へ,行ったこと,が,あります | 北海道 へ 行ったこと が あります | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Experiences & Plans (#32) | 184 | word_selection | 来月 引っ越す つもり です | 来月,引っ越す,つもり,です | 来月 引っ越す つもり です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Experiences & Plans (#32) | 185 | word_selection | 時間 が あれば 美術館 に 行きたいです | 時間,が,あれば,美術館,に,行きたいです | 時間 が あれば 美術館 に 行きたいです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Experiences & Plans (#32) | 186 | word_selection | 日本料理 を 作ってみたけれど 少し 塩辛く なりました | 日本料理,を,作ってみたけれど,少し,塩辛く,なりました | 日本料理 を 作ってみたけれど 少し 塩辛く なりました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| A2 | Experiences & Plans (#32) | 187 | word_selection | 忙しかったので 友だち に 会えませんでした | 忙しかったので,友だち,に,会えませんでした | 忙しかったので 友だち に 会えませんでした | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Opinions & Reasons (#33) | 188 | word_selection | 私は 公共交通機関 を 利用したほうが よい と 思います | 私は,公共交通機関,を,利用したほうが,よい,と,思います | 私は 公共交通機関 を 利用したほうが よい と 思います | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Opinions & Reasons (#33) | 189 | word_selection | この 店 は 安いだけでなく 料理 も おいしいです | この,店,は,安いだけでなく,料理,も,おいしいです | この 店 は 安いだけでなく 料理 も おいしいです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Opinions & Reasons (#33) | 190 | word_selection | 時間 が 足りなかったため 発表 を 延期しました | 時間,が,足りなかったため,発表,を,延期しました | 時間 が 足りなかったため 発表 を 延期しました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Opinions & Reasons (#33) | 191 | word_selection | 環境 を 守るために 一人一人 が できること を 考えるべきです | 環境,を,守るために,一人一人,が,できること,を,考えるべきです | 環境 を 守るために 一人一人 が できること を 考えるべきです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Opinions & Reasons (#33) | 192 | word_selection | 彼 の 意見 に 賛成する 理由 は 説明 が 具体的 だからです | 彼,の,意見,に,賛成する,理由,は,説明,が,具体的,だからです | 彼 の 意見 に 賛成する 理由 は 説明 が 具体的 だからです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Experiences & Stories (#34) | 193 | word_selection | 大学 に 入ってから 初めて 一人 で 海外旅行 を しました | 大学,に,入ってから,初めて,一人,で,海外旅行,を,しました | 大学 に 入ってから 初めて 一人 で 海外旅行 を しました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Experiences & Stories (#34) | 194 | word_selection | 子ども の ころ よく 祖母 の 家 に 泊まりに 行ったものです | 子ども,の,ころ,よく,祖母,の,家,に,泊まりに,行ったものです | 子ども の ころ よく 祖母 の 家 に 泊まりに 行ったものです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Experiences & Stories (#34) | 195 | word_selection | 道 に 迷ってしまった が 近く の 人 が 親切 に 案内してくれました | 道,に,迷ってしまった,が,近く,の,人,が,親切,に,案内してくれました | 道 に 迷ってしまった が 近く の 人 が 親切 に 案内してくれました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Experiences & Stories (#34) | 196 | word_selection | 会議 に 遅れないように 早め に 家 を 出ました | 会議,に,遅れないように,早め,に,家,を,出ました | 会議 に 遅れないように 早め に 家 を 出ました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Experiences & Stories (#34) | 197 | word_selection | 忙しい 時期 を 乗り越えた 経験 が 今 の 仕事 に 役立っています | 忙しい,時期,を,乗り越えた,経験,が,今,の,仕事,に,役立っています | 忙しい 時期 を 乗り越えた 経験 が 今 の 仕事 に 役立っています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Social Conversations (#35) | 198 | word_selection | もし よかったら 今度 一緒 に 食事 に 行きませんか | もし,よかったら,今度,一緒,に,食事,に,行きませんか | もし よかったら 今度 一緒 に 食事 に 行きませんか | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Social Conversations (#35) | 199 | word_selection | せっかく 誘っていただいた のに その日 は 都合 が つきません | せっかく,誘っていただいた,のに,その日,は,都合,が,つきません | せっかく 誘っていただいた のに その日 は 都合 が つきません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Social Conversations (#35) | 200 | word_selection | 先日 は いろいろ 相談 に 乗ってくれて ありがとう | 先日,は,いろいろ,相談,に,乗ってくれて,ありがとう | 先日 は いろいろ 相談 に 乗ってくれて ありがとう | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Social Conversations (#35) | 201 | word_selection | 念 の ため 集合時間 を もう一度 確認しておきます | 念,の,ため,集合時間,を,もう一度,確認しておきます | 念 の ため 集合時間 を もう一度 確認しておきます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Social Conversations (#35) | 202 | word_selection | 相手 の 立場 を 考えながら 丁寧 に 伝えること が 大切です | 相手,の,立場,を,考えながら,丁寧,に,伝えること,が,大切です | 相手 の 立場 を 考えながら 丁寧 に 伝えること が 大切です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Everyday Reading (#36) | 203 | word_selection | 調査 によると 通勤時間 が 短い 人 ほど 満足度 が 高い そうです | 調査,によると,通勤時間,が,短い,人,ほど,満足度,が,高い,そうです | 調査 によると 通勤時間 が 短い 人 ほど 満足度 が 高い そうです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Everyday Reading (#36) | 204 | word_selection | 筆者 は 便利さ だけでなく 地域 と の つながり も 重視しています | 筆者,は,便利さ,だけでなく,地域,と,の,つながり,も,重視しています | 筆者 は 便利さ だけでなく 地域 と の つながり も 重視しています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Everyday Reading (#36) | 205 | word_selection | 利用者 が 増えた 一方 で 運営費 の 確保 が 課題 と なっています | 利用者,が,増えた,一方,で,運営費,の,確保,が,課題,と,なっています | 利用者 が 増えた 一方 で 運営費 の 確保 が 課題 と なっています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Everyday Reading (#36) | 206 | word_selection | この 制度 が 始まった の は 住民 の 要望 が きっかけでした | この,制度,が,始まった,の,は,住民,の,要望,が,きっかけでした | この 制度 が 始まった の は 住民 の 要望 が きっかけでした | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B1 | Everyday Reading (#36) | 207 | word_selection | したがって 結果 を 判断する に は さらに 長期的 な 調査 が 必要です | したがって,結果,を,判断する,に,は,さらに,長期的,な,調査,が,必要です | したがって 結果 を 判断する に は さらに 長期的 な 調査 が 必要です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Formal & Informal Japanese (#37) | 208 | word_selection | 部長 が いらっしゃる まで こちら で お待ちいただけますか | 部長,が,いらっしゃる,まで,こちら,で,お待ちいただけますか | 部長 が いらっしゃる まで こちら で お待ちいただけますか | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Formal & Informal Japanese (#37) | 209 | word_selection | 本日中 に ご確認いただけますと 幸いです | 本日中,に,ご確認いただけますと,幸いです | 本日中 に ご確認いただけますと 幸いです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Formal & Informal Japanese (#37) | 210 | word_selection | それ は 知っている ものの 詳しい 経緯 まで は 把握していません | それ,は,知っている,ものの,詳しい,経緯,まで,は,把握していません | それ は 知っている ものの 詳しい 経緯 まで は 把握していません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Formal & Informal Japanese (#37) | 211 | word_selection | 会議 の 開始時刻 が 変更 に なった と の ことです | 会議,の,開始時刻,が,変更,に,なった,と,の,ことです | 会議 の 開始時刻 が 変更 に なった と の ことです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Formal & Informal Japanese (#37) | 212 | word_selection | お客様 の ご要望 に 沿えるよう 担当者 と 調整いたします | お客様,の,ご要望,に,沿えるよう,担当者,と,調整いたします | お客様 の ご要望 に 沿えるよう 担当者 と 調整いたします | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Work & Society (#38) | 213 | word_selection | 人手不足 に 伴い 業務 の 一部 を 自動化する 企業 が 増えています | 人手不足,に,伴い,業務,の,一部,を,自動化する,企業,が,増えています | 人手不足 に 伴い 業務 の 一部 を 自動化する 企業 が 増えています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Work & Society (#38) | 214 | word_selection | この 提案 を 実現する に は 費用対効果 を 慎重 に 検討する 必要 が あります | この,提案,を,実現する,に,は,費用対効果,を,慎重,に,検討する,必要,が,あります | この 提案 を 実現する に は 費用対効果 を 慎重 に 検討する 必要 が あります | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Work & Society (#38) | 215 | word_selection | 柔軟 な 働き方 が 普及する 一方 で 評価制度 の 見直し も 求められています | 柔軟,な,働き方,が,普及する,一方,で,評価制度,の,見直し,も,求められています | 柔軟 な 働き方 が 普及する 一方 で 評価制度 の 見直し も 求められています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Work & Society (#38) | 216 | word_selection | 部署間 で 情報 を 共有しなければ 同じ 問題 が 繰り返されかねません | 部署間,で,情報,を,共有しなければ,同じ,問題,が,繰り返されかねません | 部署間 で 情報 を 共有しなければ 同じ 問題 が 繰り返されかねません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Work & Society (#38) | 217 | word_selection | 彼女 は 経験 を 生かして 新規事業 の 立ち上げ に 貢献しました | 彼女,は,経験,を,生かして,新規事業,の,立ち上げ,に,貢献しました | 彼女 は 経験 を 生かして 新規事業 の 立ち上げ に 貢献しました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | News & Information (#39) | 218 | word_selection | 政府 は 物価 の 上昇 を 受け 家計支援策 を 発表しました | 政府,は,物価,の,上昇,を,受け,家計支援策,を,発表しました | 政府 は 物価 の 上昇 を 受け 家計支援策 を 発表しました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | News & Information (#39) | 219 | word_selection | 専門家 に よれば 短期的 な 効果 は 期待できる ものの 課題 も 残ります | 専門家,に,よれば,短期的,な,効果,は,期待できる,ものの,課題,も,残ります | 専門家 に よれば 短期的 な 効果 は 期待できる ものの 課題 も 残ります | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | News & Information (#39) | 220 | word_selection | 発表された 統計 は 前年 を 上回った が 業界差 が 大きい と 指摘されています | 発表された,統計,は,前年,を,上回った,が,業界差,が,大きい,と,指摘されています | 発表された 統計 は 前年 を 上回った が 業界差 が 大きい と 指摘されています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | News & Information (#39) | 221 | word_selection | 報道内容 を 理解する に は 背景 に ある 制度 も 把握する 必要 が あります | 報道内容,を,理解する,に,は,背景,に,ある,制度,も,把握する,必要,が,あります | 報道内容 を 理解する に は 背景 に ある 制度 も 把握する 必要 が あります | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | News & Information (#39) | 222 | word_selection | 一連 の 動き を 踏まえる と 今後 は 慎重 な 対応 が 求められます | 一連,の,動き,を,踏まえる,と,今後,は,慎重,な,対応,が,求められます | 一連 の 動き を 踏まえる と 今後 は 慎重 な 対応 が 求められます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Complex Conversations (#40) | 223 | word_selection | ご指摘 の 点 は もっとも です が 別 の 観点 から も 検討すべきです | ご指摘,の,点,は,もっとも,です,が,別,の,観点,から,も,検討すべきです | ご指摘 の 点 は もっとも です が 別 の 観点 から も 検討すべきです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Complex Conversations (#40) | 224 | word_selection | 納期 を 守る ため と は いえ 品質確認 を 省く わけ に は いきません | 納期,を,守る,ため,と,は,いえ,品質確認,を,省く,わけ,に,は,いきません | 納期 を 守る ため と は いえ 品質確認 を 省く わけ に は いきません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Complex Conversations (#40) | 225 | word_selection | 彼 が 反対した の は 計画 そのもの より 説明 が 不足していた から です | 彼,が,反対した,の,は,計画,そのもの,より,説明,が,不足していた,から,です | 彼 が 反対した の は 計画 そのもの より 説明 が 不足していた から です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Complex Conversations (#40) | 226 | word_selection | 話し合い を 重ねた 結果 双方 が 受け入れられる 案 に まとまりました | 話し合い,を,重ねた,結果,双方,が,受け入れられる,案,に,まとまりました | 話し合い を 重ねた 結果 双方 が 受け入れられる 案 に まとまりました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| B2 | Complex Conversations (#40) | 227 | word_selection | 状況 が 変わり次第 速やか に 関係者 へ 共有してください | 状況,が,変わり次第,速やか,に,関係者,へ,共有してください | 状況 が 変わり次第 速やか に 関係者 へ 共有してください | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Formal Japanese (#41) | 228 | word_selection | ご多忙 の ところ ご出席賜り 誠 に ありがとうございます | ご多忙,の,ところ,ご出席賜り,誠,に,ありがとうございます | ご多忙 の ところ ご出席賜り 誠 に ありがとうございます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Formal Japanese (#41) | 229 | word_selection | 資料 に 不備 が ございましたら ご指摘いただけます と 幸甚 に 存じます | 資料,に,不備,が,ございましたら,ご指摘いただけます,と,幸甚,に,存じます | 資料 に 不備 が ございましたら ご指摘いただけます と 幸甚 に 存じます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Formal Japanese (#41) | 230 | word_selection | 制度 の 趣旨 に 鑑み 運用方法 を 改めて 検討する 必要 が ございます | 制度,の,趣旨,に,鑑み,運用方法,を,改めて,検討する,必要,が,ございます | 制度 の 趣旨 に 鑑み 運用方法 を 改めて 検討する 必要 が ございます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Formal Japanese (#41) | 231 | word_selection | 先方 の 意向 を 踏まえた うえ で 正式 な 回答 を 差し上げます | 先方,の,意向,を,踏まえた,うえ,で,正式,な,回答,を,差し上げます | 先方 の 意向 を 踏まえた うえ で 正式 な 回答 を 差し上げます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Formal Japanese (#41) | 232 | word_selection | 本件 に つきまして は 関係各所 と 協議 の うえ 決定いたします | 本件,に,つきまして,は,関係各所,と,協議,の,うえ,決定いたします | 本件 に つきまして は 関係各所 と 協議 の うえ 決定いたします | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Abstract Topics (#42) | 233 | word_selection | 技術 の 進歩 は 利便性 を 高める 一方 新た な 倫理的課題 を 生み出しかねません | 技術,の,進歩,は,利便性,を,高める,一方,新た,な,倫理的課題,を,生み出しかねません | 技術 の 進歩 は 利便性 を 高める 一方 新た な 倫理的課題 を 生み出しかねません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Abstract Topics (#42) | 234 | word_selection | 自由 と は 単 に 制約 が ない こと で は なく 選択 に 伴う 責任 を 引き受ける こと です | 自由,と,は,単,に,制約,が,ない,こと,で,は,なく,選択,に,伴う,責任,を,引き受ける,こと,です | 自由 と は 単 に 制約 が ない こと で は なく 選択 に 伴う 責任 を 引き受ける こと です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Abstract Topics (#42) | 235 | word_selection | 文化的背景 を 異にする 人々 が 共生する に は 対話 の 場 が 欠かせません | 文化的背景,を,異にする,人々,が,共生する,に,は,対話,の,場,が,欠かせません | 文化的背景 を 異にする 人々 が 共生する に は 対話 の 場 が 欠かせません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Abstract Topics (#42) | 236 | word_selection | 経済成長 を 優先する あまり 環境 へ の 長期的影響 を 軽視して は なりません | 経済成長,を,優先する,あまり,環境,へ,の,長期的影響,を,軽視して,は,なりません | 経済成長 を 優先する あまり 環境 へ の 長期的影響 を 軽視して は なりません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Abstract Topics (#42) | 237 | word_selection | この 議論 は 効率性 と 公平性 を いかに 両立させる か という 問い に 帰着します | この,議論,は,効率性,と,公平性,を,いかに,両立させる,か,という,問い,に,帰着します | この 議論 は 効率性 と 公平性 を いかに 両立させる か という 問い に 帰着します | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Reading (#43) | 238 | word_selection | 著者 は 数値 の 上昇 を 成果 と みなす 見方 に 疑問 を 呈しています | 著者,は,数値,の,上昇,を,成果,と,みなす,見方,に,疑問,を,呈しています | 著者 は 数値 の 上昇 を 成果 と みなす 見方 に 疑問 を 呈しています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Reading (#43) | 239 | word_selection | 本文 の 主張 を 要約する なら 制度 の 持続可能性 に は 参加者 の 納得 が 不可欠です | 本文,の,主張,を,要約する,なら,制度,の,持続可能性,に,は,参加者,の,納得,が,不可欠です | 本文 の 主張 を 要約する なら 制度 の 持続可能性 に は 参加者 の 納得 が 不可欠です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Reading (#43) | 240 | word_selection | 一見 矛盾する 二つ の 記述 は 対象 と する 時間軸 の 違い から 説明できます | 一見,矛盾する,二つ,の,記述,は,対象,と,する,時間軸,の,違い,から,説明できます | 一見 矛盾する 二つ の 記述 は 対象 と する 時間軸 の 違い から 説明できます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Reading (#43) | 241 | word_selection | 前段 の 事例 は 後 に 示される 仮説 を 補強する 役割 を 果たしています | 前段,の,事例,は,後,に,示される,仮説,を,補強する,役割,を,果たしています | 前段 の 事例 は 後 に 示される 仮説 を 補強する 役割 を 果たしています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Advanced Reading (#43) | 242 | word_selection | したがって 筆者 は 短期 の 成果 だけ で 政策 を 評価すべきで は ない と 論じています | したがって,筆者,は,短期,の,成果,だけ,で,政策,を,評価すべきで,は,ない,と,論じています | したがって 筆者 は 短期 の 成果 だけ で 政策 を 評価すべきで は ない と 論じています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Nuanced Expressions (#44) | 243 | word_selection | 懸念 が ない わけ で は ありません が 現時点 で 計画 を 撤回する 必要 は ない でしょう | 懸念,が,ない,わけ,で,は,ありません,が,現時点,で,計画,を,撤回する,必要,は,ない,でしょう | 懸念 が ない わけ で は ありません が 現時点 で 計画 を 撤回する 必要 は ない でしょう | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Nuanced Expressions (#44) | 244 | word_selection | 彼 の 発言 は 批判 と いうより 改善 を 促す 提案 と 受け取るべきです | 彼,の,発言,は,批判,と,いうより,改善,を,促す,提案,と,受け取るべきです | 彼 の 発言 は 批判 と いうより 改善 を 促す 提案 と 受け取るべきです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Nuanced Expressions (#44) | 245 | word_selection | 事情 を 知らなかった に せよ 確認 を 怠った 責任 は 免れません | 事情,を,知らなかった,に,せよ,確認,を,怠った,責任,は,免れません | 事情 を 知らなかった に せよ 確認 を 怠った 責任 は 免れません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Nuanced Expressions (#44) | 246 | word_selection | 期待 に 沿う と は 限りません が 代替案 と して は 有力です | 期待,に,沿う,と,は,限りません,が,代替案,と,して,は,有力です | 期待 に 沿う と は 限りません が 代替案 と して は 有力です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C1 | Nuanced Expressions (#44) | 247 | word_selection | 言葉 を 濁した の は 断る 意図 というより 判断 を 保留したかった ため です | 言葉,を,濁した,の,は,断る,意図,というより,判断,を,保留したかった,ため,です | 言葉 を 濁した の は 断る 意図 というより 判断 を 保留したかった ため です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Vocabulary (#45) | 248 | word_selection | 前例踏襲 に 終始すれば 変化する 実情 を 見誤りかねません | 前例踏襲,に,終始すれば,変化する,実情,を,見誤りかねません | 前例踏襲 に 終始すれば 変化する 実情 を 見誤りかねません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Vocabulary (#45) | 249 | word_selection | その 提言 は 一見 穏当 ながら 既存 の 前提 を 根底 から 問い直しています | その,提言,は,一見,穏当,ながら,既存,の,前提,を,根底,から,問い直しています | その 提言 は 一見 穏当 ながら 既存 の 前提 を 根底 から 問い直しています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Vocabulary (#45) | 250 | word_selection | 彼女 の 論考 は 多義的 な 概念 を 丁寧 に 峻別しています | 彼女,の,論考,は,多義的,な,概念,を,丁寧,に,峻別しています | 彼女 の 論考 は 多義的 な 概念 を 丁寧 に 峻別しています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Vocabulary (#45) | 251 | word_selection | 拙速 な 結論 を 避け 証拠 の 射程 を 見極める べきです | 拙速,な,結論,を,避け,証拠,の,射程,を,見極める,べきです | 拙速 な 結論 を 避け 証拠 の 射程 を 見極める べきです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Vocabulary (#45) | 252 | word_selection | 措置 の 妥当性 を 論じる に は 副作用 も 勘案しなければ なりません | 措置,の,妥当性,を,論じる,に,は,副作用,も,勘案しなければ,なりません | 措置 の 妥当性 を 論じる に は 副作用 も 勘案しなければ なりません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Complex Grammar (#46) | 253 | word_selection | 制度 を 改める に あたって は 理念 のみならず 実務上 の 制約 に も 目 を 向ける べきです | 制度,を,改める,に,あたって,は,理念,のみならず,実務上,の,制約,に,も,目,を,向ける,べきです | 制度 を 改める に あたって は 理念 のみならず 実務上 の 制約 に も 目 を 向ける べきです | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Complex Grammar (#46) | 254 | word_selection | 説明責任 を 果たさず し て 社会的 な 信頼 を 回復する こと は できません | 説明責任,を,果たさず,し,て,社会的,な,信頼,を,回復する,こと,は,できません | 説明責任 を 果たさず し て 社会的 な 信頼 を 回復する こと は できません | — | PASS | WARNING: splits the expression ずして across tiles | N/A | WARNING | PASS | PASS | PASS | N/A | None | WARNING |
| C2 | Complex Grammar (#46) | 255 | word_selection | 彼 の 提案 は 現実 に 即している から こそ 関係者 の 支持 を 得た の です | 彼,の,提案,は,現実,に,即している,から,こそ,関係者,の,支持,を,得た,の,です | 彼 の 提案 は 現実 に 即している から こそ 関係者 の 支持 を 得た の です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Complex Grammar (#46) | 256 | word_selection | 一見 些細 な 差異 で あって も 文脈 を 左右しうる 以上 看過できません | 一見,些細,な,差異,で,あって,も,文脈,を,左右しうる,以上,看過できません | 一見 些細 な 差異 で あって も 文脈 を 左右しうる 以上 看過できません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Complex Grammar (#46) | 257 | word_selection | 論拠 が 十分 に 示されて 初めて 結論 の 妥当性 が 検証 に 耐えます | 論拠,が,十分,に,示されて,初めて,結論,の,妥当性,が,検証,に,耐えます | 論拠 が 十分 に 示されて 初めて 結論 の 妥当性 が 検証 に 耐えます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Reading & Interpretation (#47) | 258 | word_selection | 本稿 が 依拠する 史料 は 限られて おり 一般化 に は 慎重 を 要します | 本稿,が,依拠する,史料,は,限られて,おり,一般化,に,は,慎重,を,要します | 本稿 が 依拠する 史料 は 限られて おり 一般化 に は 慎重 を 要します | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Reading & Interpretation (#47) | 259 | word_selection | 著者 は 反対説 を 退ける の で は なく その 成立条件 を 限定しています | 著者,は,反対説,を,退ける,の,で,は,なく,その,成立条件,を,限定しています | 著者 は 反対説 を 退ける の で は なく その 成立条件 を 限定しています | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Reading & Interpretation (#47) | 260 | word_selection | この 比喩 は 制度 の 硬直性 を 示唆する ものの 全面的 な 否定 を 意味しません | この,比喩,は,制度,の,硬直性,を,示唆する,ものの,全面的,な,否定,を,意味しません | この 比喩 は 制度 の 硬直性 を 示唆する ものの 全面的 な 否定 を 意味しません | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Reading & Interpretation (#47) | 261 | word_selection | 後半 の 譲歩表現 から 読み取れる の は 筆者 が 原則 と 例外 を 区別している 点 です | 後半,の,譲歩表現,から,読み取れる,の,は,筆者,が,原則,と,例外,を,区別している,点,です | 後半 の 譲歩表現 から 読み取れる の は 筆者 が 原則 と 例外 を 区別している 点 です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Advanced Reading & Interpretation (#47) | 262 | word_selection | 結論 は 本文 の 事例 を 超えて 適用できる か なお 検討 の 余地 が あります | 結論,は,本文,の,事例,を,超えて,適用できる,か,なお,検討,の,余地,が,あります | 結論 は 本文 の 事例 を 超えて 適用できる か なお 検討 の 余地 が あります | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Nuance, Idioms & Formal Context (#48) | 263 | word_selection | 彼 は 面目 を 保つ ため と は いえ 本質的 な 論点 から 目 を そらした | 彼,は,面目,を,保つ,ため,と,は,いえ,本質的,な,論点,から,目,を,そらした | 彼 は 面目 を 保つ ため と は いえ 本質的 な 論点 から 目 を そらした | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Nuance, Idioms & Formal Context (#48) | 264 | word_selection | その 判断 は 時宜 を 得ている 反面 将来 へ の 布石 と して は 不十分です | その,判断,は,時宜,を,得ている,反面,将来,へ,の,布石,と,して,は,不十分です | その 判断 は 時宜 を 得ている 反面 将来 へ の 布石 と して は 不十分です | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Nuance, Idioms & Formal Context (#48) | 265 | word_selection | 言外 に 含まれた 皮肉 を 汲み取れなければ 発言 の 真意 を 取り違えます | 言外,に,含まれた,皮肉,を,汲み取れなければ,発言,の,真意,を,取り違えます | 言外 に 含まれた 皮肉 を 汲み取れなければ 発言 の 真意 を 取り違えます | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Nuance, Idioms & Formal Context (#48) | 266 | word_selection | 慣例 に 倣う こと が 常 に 最善 と は 限らず 時 に は 旧弊 を 温存します | 慣例,に,倣う,こと,が,常,に,最善,と,は,限らず,時,に,は,旧弊,を,温存します | 慣例 に 倣う こと が 常 に 最善 と は 限らず 時 に は 旧弊 を 温存します | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |
| C2 | Nuance, Idioms & Formal Context (#48) | 267 | word_selection | 彼 は 一歩 も 譲らない よう に 見えて 最終的 に は 妥協点 を 探りました | 彼,は,一歩,も,譲らない,よう,に,見えて,最終的,に,は,妥協点,を,探りました | 彼 は 一歩 も 譲らない よう に 見えて 最終的 に は 妥協点 を 探りました | — | PASS | PASS | N/A | PASS | PASS | PASS | PASS | N/A | None | PASS |

## Audit summary

- Lessons: 24 (four in each of A1, A2, B1, B2, C1, C2).
- Existing questions: 128.
- Duplicate question tuples: 0 rows in duplicate groups; none found.
- Broken image file paths: none; all three stored files exist. The image content/labels for Q23 are mismatched, as noted above.
- Unsupported-in-training type: one `audio` row (Q18), with a null audio path.
- Translation tiles before insertion: none.

## Final content run (after corrections and seeding)

The existing content seeder was extended and run twice. It did not delete rows. It corrected the three audited rows in place, aligned the four A1 lesson titles/descriptions/order to the requested structure, and added questions only to bring each lesson to 10. The second run did not increase the count.

| Level | Lesson | Existing Questions | Valid | Warning | Error | New Questions | Final Total |
|---|---|---:|---:|---:|---:|---:|---:|
| A1 | Greetings & Self Introduction | 9 | 9 | 0 | 0 | 1 | 10 |
| A1 | Numbers, Time & Dates | 9 | 5 | 4 | 0 | 1 | 10 |
| A1 | Daily Life | 5 | 5 | 0 | 0 | 5 | 10 |
| A1 | Places & Activities | 5 | 5 | 0 | 0 | 5 | 10 |
| A2 | Daily Routines | 5 | 5 | 0 | 0 | 5 | 10 |
| A2 | Shopping & Food | 5 | 5 | 0 | 0 | 5 | 10 |
| A2 | Travel & Directions | 5 | 5 | 0 | 0 | 5 | 10 |
| A2 | Experiences & Plans | 5 | 5 | 0 | 0 | 5 | 10 |
| B1 | Opinions & Reasons | 5 | 5 | 0 | 0 | 5 | 10 |
| B1 | Experiences & Stories | 5 | 5 | 0 | 0 | 5 | 10 |
| B1 | Social Conversations | 5 | 5 | 0 | 0 | 5 | 10 |
| B1 | Everyday Reading | 5 | 5 | 0 | 0 | 5 | 10 |
| B2 | Formal & Informal Japanese | 5 | 5 | 0 | 0 | 5 | 10 |
| B2 | Work & Society | 5 | 5 | 0 | 0 | 5 | 10 |
| B2 | News & Information | 5 | 5 | 0 | 0 | 5 | 10 |
| B2 | Complex Conversations | 5 | 5 | 0 | 0 | 5 | 10 |
| C1 | Advanced Formal Japanese | 5 | 5 | 0 | 0 | 5 | 10 |
| C1 | Abstract Topics | 5 | 5 | 0 | 0 | 5 | 10 |
| C1 | Advanced Reading | 5 | 5 | 0 | 0 | 5 | 10 |
| C1 | Nuanced Expressions | 5 | 5 | 0 | 0 | 5 | 10 |
| C2 | Advanced Vocabulary | 5 | 5 | 0 | 0 | 5 | 10 |
| C2 | Complex Grammar | 5 | 5 | 0 | 0 | 5 | 10 |
| C2 | Advanced Reading & Interpretation | 5 | 5 | 0 | 0 | 5 | 10 |
| C2 | Nuance, Idioms & Formal Context | 5 | 5 | 0 | 0 | 5 | 10 |

### Corrections and final checks

- Q18 (ID 18) changed from unsupported `audio` to supported `word_selection`; its prompt asks for the reading of 水. Choices and answer were retained.
- Q23 (ID 23) retains all three existing image paths. The prompt and options now identify the visible game, code, and terminal screens; the correct answer is `ゲーム画面`.
- Q254 (ID 254) now uses the grammatical combined phrase `果たさずして` as one phrase tile.
- Four retained A1 Numbers, Time & Dates questions are structurally valid object/vocabulary questions but outside that lesson's topic. They remain warnings and were not discarded.
- Before seeding there were 128 rows and no duplicate tuples. Final data has 240 rows, all 24 lessons have 10, no duplicate `(lesson_id, question, question_type, correct_answer)` groups, no null/empty required fields, and only the four supported Training types.
- Final type counts: `word_selection` 148, `phrase_ordering` 23, `translation_tiles` 68, `image_selection` 1. The unsupported `audio` row was corrected; no other unsupported types remain.
- All three existing image files still exist and stored paths were preserved. No image paths were invented.
- The Super Admin form offers `translation_tiles` and explains Japanese prompt / English tiles / correct English sentence. Training shuffles the English tiles, and the server validates the ordered answer. Phrase ordering uses the existing Japanese-token behavior.
- Migration/runtime schema drift remains: migration history declares `audio_path` and does not create `image_url`, while runtime model/database use `audio` and `image_url`. No migration or schema was changed.

### Idempotency

Run with `php artisan db:seed --class=Database\\Seeders\\A1ToC2LearningContentSeeder`. The live database had 128 rows. First run added 112 questions and corrected three audited rows in place, resulting in 240. Second run retained 240 with no duplicate or count increase. Matching supplemental rows are synchronized by lesson, type, and prompt so corrected data updates in place.

### Remaining notes

- No unresolved broken image references, unsupported question types, missing required fields, or translation answer/tile mismatches were found in the final content.
- The existing migration/runtime schema drift should be reconciled separately before relying on a fresh database migration build; it was left unchanged here.
- Verified existing answer construction: all word/phrase rows can be assembled from their options; image Q23 answer is one of its options.
- Known corrections to preserve IDs: Q18 (`audio`→supported word selection); Q23 prompt/options and JSON keys will be aligned to the existing image contents without changing file paths; Q254 will combine `ずして` into one grammatical tile.

