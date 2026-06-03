<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard\Dataset;

/**
 * PHP array implementation of PatternDatasetInterface for unethical content.
 *
 * Bilingual (Persian + English) patterns across 7 categories.
 * Swap this binding in AIServiceLoader to source patterns from any backend.
 */
final readonly class IllegalPatternDataset
{
	/** @return array<string, string> */
	public static function selfHarm(): array
	{
		return [
			'(?i)(how\s+to|ways?\s+to|steps?\s+to|best\s+way\s+to).{0,30}(kill\s+(my)?self|end\s+(my\s+)?life|commit\s+suicide)' => 'self-harm: suicide method request (EN)',
			'(?i)(want\s+to|going\s+to|plan\s+to|thinking\s+of|decided\s+to).{0,30}(commit\s+suicide|kill\s+myself|end\s+my\s+life|take\s+my\s+own\s+life)' => 'self-harm: suicide method request (EN)',
			'(?i)(cut\s+yourself|self[\s\-]?harm|hurt\s+yourself|slit\s+(my|your|her|his)\s+wrist|slash\s+(my|your)\s+wrist)' => 'self-harm: cutting or self-injury (EN)',
			'(?i)(overdos(e|ing)|take\s+too\s+many\s+pills|swallow.{0,20}pills).{0,30}(to\s+die|to\s+end|kill)' => 'self-harm: overdose method (EN)',
			'(?i)(no\s+(reason|point|purpose)\s+(left\s+)?to\s+(live|go\s+on|keep\s+going)|life\s+(is\s+not|isn\'t)\s+worth\s+(living|it))' => 'self-harm: passive suicidal ideation (EN)',
			'(?i)(چطور|چگونه|روش|بهترین\s+راه).{0,30}(خودکشی|خودم\s+رو\s+بکشم|به\s+زندگیم\s+پایان\s+بدم)' => 'self-harm: suicide method request (FA)',
			'(?i)(می.?خوام\s+بمیرم|دیگه\s+نمی.?خوام\s+زندگی\s+کنم).{0,50}(چطور|روش|کمک)' => 'self-harm: passive suicidal ideation with help-seeking (FA)',
			'(?i)(خودآزاری|آسیب\s+به\s+خود|رگ\s+زدن|تیغ\s+زدن\s+به\s+خود|بریدن\s+دست)' => 'self-harm: self-injury (FA)',
			'(?i)(می.?خوام\s+بمیرم|دیگه\s+نمی.?خوام\s+زندگی\s+کنم|خسته\s+از\s+زندگی\s+شدم).{0,50}(کمکم\s+کن|چطور|روش)' => 'self-harm: passive suicidal ideation with help-seeking (FA)',
			'(?i)(قرص\s+زیاد\s+خوردن|مصرف\s+اضافه.{0,10}دارو).{0,20}(برای\s+(مردن|پایان))' => 'self-harm: overdose method (FA)',
		];
	}

	/** @return array<string, string> */
	public static function harassment(): array
	{
		return [
			'(?i)\b(worthless|subhuman|piece\s+of\s+(shit|garbage|trash)|you\s+deserve\s+to\s+die|go\s+kill\s+yourself|good\s+for\s+nothing)\b' => 'harassment: dehumanising insult (EN)',
			'(?i)(i\s+will\s+|i\'m\s+going\s+to\s+|i\'ll\s+).{0,20}(hurt|destroy|ruin|rape|kill|beat)\s+you' => 'harassment: direct personal threat (EN)',
			'(?i)(send|spread|post|share|leak).{0,30}(nudes?|intimate\s+photos?|private\s+(images?|videos?)).{0,30}(of\s+you|of\s+her|of\s+him|everywhere)' => 'harassment: non-consensual intimate image threat (EN)',
			'(?i)(you\s+deserve\s+to\s+(suffer|rot|be\s+alone|be\s+hurt)|nobody\s+(loves?|cares?\s+about)\s+you)' => 'harassment: dehumanising threat (EN)',
			'(?i)(بی.?ارزش|آشغال|حیوان|خفه\s+شو|گمشو\s+بمیر|برو\s+بمیر|لایق\s+مرگی|پست\s+فطرت)' => 'harassment: dehumanising insult (FA)',
			'(?i)(می.?کشمت|نابودت\s+می.?کنم|حسابت\s+رو\s+می.?رسم|تلافی\s+می.?کنم|جواب\s+کارت\s+رو\s+می.?دم)' => 'harassment: direct personal threat (FA)',
			'(?i)(عکس|فیلم).{0,20}(لخت|خصوصی|سکسی).{0,30}(پخش|منتشر|لیک)\s+(می.?کنم|خواهم\s+کرد|می.?دم)' => 'harassment: non-consensual intimate image threat (FA)',
			'(?i)(هیچ.?کس\s+(دوستت\s+نداره|برات\s+مهم\s+نیست)|ارزش\s+(زندگی\s+کردن|ادامه\s+دادن)\s+نداری)' => 'harassment: dehumanising threat (FA)',
		];
	}

	/** @return array<string, string> */
	public static function manipulation(): array
	{
		return [
			'(?i)(gaslight(ing)?|manipulat(e|ing|ion)|psycholog(y|ical)\s+(trick|manipulation|abuse|warfare))' => 'manipulation: psychological abuse pattern (EN)',
			'(?i)(make\s+(her|him|them|someone)\s+feel\s+(crazy|insane|worthless|dependent)|control\s+(her|him|their|my|someone\'?s?)\s+mind)' => 'manipulation: coercive control tactic (EN)',
			'(?i)(ways?\s+to|how\s+to).{0,30}(psychologically\s+control|mentally\s+control|emotionally\s+abuse|brainwash).{0,20}(my|her|his|your|a\s+person|someone)' => 'manipulation: coercive control tactic (EN)',
			'(?i)(love\s*bomb(ing)?|isolat(e|ing)\s+(her|him|them)\s+from|coercive\s+control|dark\s+psycholog(y|ical)\s+trick)' => 'manipulation: coercive control tactic (EN)',
			'(?i)(دستکاری\s+(ذهن|احساس)|گَزلایت|روان.?شناسی\s+تاریک|کنترل\s+(ذهن|روان)\s+(دیگری|او|اون))' => 'manipulation: psychological abuse (FA)',
			'(?i)(چطور\s+(کنترلش\s+کنم|مجبورش\s+کنم|وابسته.ام\s+کنم|حرفم\s+رو\s+گوش\s+کنه\s+به\s+زور))' => 'manipulation: coercive control tactic (FA)',
			'(?i)(روش.?های\s+(دستکاری|کنترل)\s+ذهن\s+(دیگران|کسی|شریک))' => 'manipulation: coercive control tactic (FA)',
			'(?i)(لاو\s+بمبینگ|انزوا\s+دادن\s+از\s+(خانواده|دوستان)|کنترل\s+اقتصادی\s+شریک)' => 'manipulation: coercive control tactic (FA)',
		];
	}

	/** @return array<string, string> */
	public static function blackmail(): array
	{
		return [
			'(?i)(blackmail|extort(ion)?|threaten.{0,20}(expose|release|leak|publish).{0,30}(photos?|videos?|secret|information))' => 'extortion: blackmail or image-based threat (EN)',
			'(?i)(pay\s+(me|us).{0,20}(or|otherwise).{0,30}(expose|reveal|publish|send\s+to\s+(your|everyone)))' => 'extortion: ransom demand pattern (EN)',
			'(?i)(i\s+will\s+|i\'ll\s+)(leak|expose|publish|share|post|send).{0,30}(your\s+)?(photos?|videos?|images?|nudes?|pictures?|secrets?)' => 'extortion: blackmail or image-based threat (EN)',
			'(?i)(revenge\s+porn|non.?consensual\s+(intimate|nude|sexual)\s+(image|video|photo))' => 'extortion: non-consensual intimate image (EN)',
			'(?i)(sextort(ion)?|online\s+blackmail\s+with\s+(photos?|videos?))' => 'extortion: sextortion (EN)',
			'(?i)(باج|اخاذی|تهدید.{0,20}(افشا|لیک|منتشر).{0,30}(عکس|فیلم|اطلاعات|مکالمه))' => 'extortion: blackmail (FA)',
			'(?i)(اخاذی\s+از\s+طریق\s+تهدید|اخاذی\s+اینترنتی|باج.?گیری\s+آنلاین)' => 'extortion: blackmail (FA)',
			'(?i)(پول\s+(بده|بفرست).{0,30}(وگرنه|وگر\s+نه|در\s+غیر\s+این\s+صورت).{0,40}(پخش|لو\s+می.?دم|به\s+همه\s+می.?گم|منتشر))' => 'extortion: ransom demand (FA)',
			'(?i)(پورن\s+انتقامی|انتشار\s+تصاویر\s+خصوصی\s+بدون\s+رضایت)' => 'extortion: non-consensual intimate image (FA)',
		];
	}

	/** @return array<string, string> */
	public static function stalking(): array
	{
		return [
			'(?i)(stalk(ing|er)?|follow\s+(her|him|them)\s+home|track\s+(her|him|their|my|someone.?s?)\s+(location|phone|device|movements?))' => 'stalking: location surveillance (EN)',
			'(?i)(track|monitor|locate|find).{0,20}(my|her|his)\s+(ex|partner|girlfriend|boyfriend|wife|husband).{0,20}(location|whereabouts|phone|gps)' => 'stalking: location surveillance (EN)',
			'(?i)(spy\s+(on|app)|install.{0,20}(spyware|tracker|spy\s+software).{0,20}(on\s+(her|his|their|someone.?s?)\s+phone|without))' => 'stalking: spyware installation (EN)',
			'(?i)(read\s+(her|his|their|someone.?s?)\s+(private\s+)?(messages?|texts?|emails?)\s+without|hack.{0,15}(phone|account)\s+to\s+(read|see|monitor))' => 'stalking: digital surveillance (EN)',
			'(?i)(تعقیب\s+کردن|دنبال\s+کردن\s+(مخفیانه|بدون\s+اطلاع)|موقعیت\s+(مکانی|جغرافیایی).{0,20}(پیدا\s+کردن|ردیابی))' => 'stalking: location tracking (FA)',
			'(?i)(نرم.?افزار\s+(جاسوسی|ردیابی)|کی.?لاگر|روی\s+(گوشی|تلفن).{0,20}(نصب\s+کنم|بذارم).{0,20}(بدون\s+اطلاعش|مخفیانه))' => 'stalking: covert spyware (FA)',
			'(?i)(نرم.?افزار\s+جاسوسی\s+روی\s+(گوشی|تلفن).{0,10}نصب\s+کنم)' => 'stalking: covert spyware (FA)',
			'(?i)(read|access|check|see|view)\s+(my|her|his)\s+(partner.?s?|ex.?s?|girlfriend.?s?|boyfriend.?s?|wife.?s?|husband.?s?)\s+(private\s+)?(messages?|texts?|chats?|emails?)\s+(without|secretly|covertly)' => 'stalking: digital surveillance (EN)',
			'(?i)(find\s+out\s+where|find\s+where|locate\s+where)\s+(my|her|his)\s+(ex|partner|girlfriend|boyfriend|wife|husband)\s+(is|lives?|stays?|went|went\s+to)' => 'stalking: location surveillance (EN)',
			'(?i)(پیام.?های\s+(واتساپ|تلگرام|اینستاگرام|خصوصی).{0,15}بدون\s+اطلاعش\s+(بخونم|ببینم|دسترسی))' => 'stalking: digital surveillance (FA)',
			'(?i)(ردیابی\s+موقعیت\s+مکانی.{0,15}بدون\s+اطلاعش|موقعیت.{0,10}همسرم.{0,10}بدون\s+اطلاعش)' => 'stalking: location tracking (FA)',
			'(?i)(پیام.?های\s+(خصوصی|واتساپ|اینستاگرام).{0,15}(بدون\s+اطلاعش\s+)?(بخونم|ببینم)|هک\s+(کردن\s+)?(گوشی|حساب).{0,15}برای\s+(خوندن|دیدن))' => 'stalking: digital surveillance (FA)',
		];
	}

	/** @return array<string, string> */
	public static function nonConsensual(): array
	{
		return [
			'(?i)(non.?consensual|without\s+(their\s+|her\s+|his\s+)?consent|force\s+(her|him|them)\s+to\s+(have\s+sex|sleep|do\s+sexual))' => 'non-consensual: sexual coercion (EN)',
			'(?i)(drug.{0,20}(drink|food|water).{0,30}(to\s+(rape|sleep\s+with|take\s+advantage|assault))|date\s+rape\s+drug|roofie)' => 'non-consensual: drug-facilitated assault (EN)',
			'(?i)(how\s+to\s+rape|rape\s+(guide|tutorial|method|trick)|tips\s+for\s+sexual\s+assault)' => 'non-consensual: sexual assault instruction (EN)',
			'(?i)(make\s+(her|him)\s+unable\s+to\s+(resist|fight\s+back|say\s+no).{0,30}(sex|assault|take\s+advantage))' => 'non-consensual: sexual coercion (EN)',
			'(?i)(بدون\s+رضایت|به\s+زور\s+(رابطه|جنسی|همبستری)|تجاوز\s+(جنسی|به\s+زن|به\s+مرد|به\s+دختر))' => 'non-consensual: sexual assault (FA)',
			'(?i)(داروی\s+بی.?هوشی|قرص\s+(خواب.?آور|بی.?هوشی).{0,30}(توی\s+(نوشیدنی|غذا)|به\s+خوردش\s+بدم))' => 'non-consensual: drug-facilitated assault (FA)',
			'(?i)(داروی\s+بی.?هوشی\s+توی\s+نوشیدنی.{0,10}(بریز|بذار))' => 'non-consensual: drug-facilitated assault (FA)',
			'(?i)(آموزش\s+تجاوز|روش\s+تجاوز|چطور\s+تجاوز\s+کنم)' => 'non-consensual: sexual assault instruction (FA)',
		];
	}

	/** @return array<string, string> */
	public static function hateSpeech(): array
	{
		return [
			'(?i)(all\s+(muslims?|jews?|christians?|iranians?|arabs?|blacks?|whites?|immigrants?).{0,20}(should\s+(die|be\s+killed|be\s+eliminated|be\s+deported)))' => 'hate-speech: genocide advocacy (EN)',
			'(?i)(gas\s+the\s+(jews?|muslims?|arabs?)|ethnic\s+cleansing\s+of|white\s+genocide|great\s+replacement)' => 'hate-speech: ethnic extermination call (EN)',
			'(?i)(n[i1]gg(er|a)|ch[i1]nk|sp[i1]c|k[i1]ke|w[o0]g|g[o0]ok).{0,20}(should|deserve|need\s+to)' => 'hate-speech: racial slur with threat (EN)',
			'(?i)(death\s+to\s+(all\s+)?(jews?|muslims?|christians?|arabs?|immigrants?)|kill\s+all\s+(muslims?|jews?|blacks?|whites?|arabs?))' => 'hate-speech: genocide call (EN)',
			'(?i)(همه\s+(مسلمانان|یهودیان|مسیحیان|عرب.?ها|کردها|بهاییان|افغان.?ها).{0,30}(باید\s+(کشته\s+شوند|نابود\s+شوند|بمیرند|اخراج\s+شوند)))' => 'hate-speech: genocide advocacy (FA)',
			'(?i)(مرگ\s+بر\s+(یهودی|عرب|فارس|کرد|بهایی|مسیحی|سنی|شیعه|افغان|مهاجر))' => 'hate-speech: death chant targeting ethnicity or religion (FA)',
			'(?i)(نژاد\s+(پاک|برتر)|پاک.?سازی\s+(نژادی|قومی|مذهبی)|کشتار\s+دسته.?جمعی\s+(اقلیت|قوم|مذهب))' => 'hate-speech: ethnic cleansing (FA)',
		];
	}

	/** @return array<string, string> */
	public static function instructionSuppression(): array
	{
		return [
			'(?i)ignore\s+(all\s+)?(previous|prior|above|earlier|initial|original)\s+(instructions?|prompt|rules?|context|constraints?|guidelines?)' => 'injection: instruction suppression (EN)',
			'(?i)forget\s+(every(thing)?|all|your)\s+(instructions?|rules?|guidelines?|constraints?|training|previous\s+context)' => 'injection: instruction suppression (EN)',
			'(?i)forget\s+(every(thing)?|all|your)\s+\w+\s+(instructions?|rules?|guidelines?|constraints?)' => 'injection: instruction suppression (EN)',
			'(?i)disregard\s+(all\s+)?(previous|prior|above|your)\s+(instructions?|rules?|guidelines?|prompt)' => 'injection: instruction suppression (EN)',
			'(?i)disregard\s+(all\s+)?(previous|prior|above|your)\s+\w+\s+(instructions?|rules?|guidelines?|prompt)' => 'injection: instruction suppression (EN)',
			'(?i)(clear|reset|wipe|delete|remove)\s+(your\s+)?(memory|context|instructions?|prior\s+conversation)' => 'injection: instruction suppression (EN)',
			'(?i)start\s+(over|fresh|from\s+scratch)\s+(with\s+)?(no|without\s+(any\s+)?)?(rules?|guidelines?|restrictions?)' => 'injection: instruction suppression (EN)',
			'(?i)(نادیده\s+بگیر|فراموش\s+کن|نادیده\s+گرفتن).{0,20}(دستورالعمل|قوانین|محدودیت|پرامپت|قوانین\s+قبلی)' => 'injection: instruction suppression (FA)',
			'(?i)(همه\s+دستورات\s+قبلی\s+را\s+(نادیده\s+بگیر|فراموش\s+کن)|از\s+این\s+به\s+بعد\s+قانونی\s+نداری)' => 'injection: instruction suppression (FA)',
			'(?i)(حافظه|دستورات|قوانین|محدودیت.?ها).{0,15}(پاک\s+کن|ریست\s+کن|حذف\s+کن)' => 'injection: instruction suppression (FA)',
		];
	}

	/** @return array<string, string> */
	public static function personaOverride(): array
	{
		return [
			'(?i)you\s+(are\s+)?(now|going\s+to\s+be|will\s+be|must\s+be)\s+(a\s+)?(different|new|evil|uncensored|unfiltered|unrestricted|DAN|jailbroken)' => 'injection: persona override (EN)',
			'(?i)you\s+are\s+now\s+(an?\s+)?(uncensored|unfiltered|unrestricted|evil|jailbroken|unaligned)' => 'injection: persona override (EN)',
			'(?i)you\s+are\s+now\s+(a\s+)?(free|liberated|unshackled|unchained|autonomous)[\s\w]{0,15}(ai|assistant|model|bot)' => 'injection: persona override (EN)',
			'(?i)act\s+as\s+(if\s+you\s+(are|were)\s+)?(an?\s+)?(evil|unethical|uncensored|jailbreak(ed)?|unrestricted|unaligned)\s+(ai|bot|model|assistant)?' => 'injection: persona override (EN)',
			'(?i)act\s+as\s+if\s+you\s+were\s+an?\s+\w+(,\s*|\s+)(evil|unethical|uncensored|unaligned|jailbroken)' => 'injection: persona override (EN)',
			'(?i)(from\s+now\s+on|starting\s+now).{0,20}you\s+(are|will\s+be|must\s+act\s+as)\s+(a\s+)?(different|uncensored|unfiltered)' => 'injection: persona override (EN)',
			'(?i)(pretend|imagine|roleplay|assume)\s+(you\s+(are|have\s+no)|there\s+(are|is)\s+no)\s+(rules?|restrictions?|guidelines?|filters?|ethics?)' => 'injection: fictional framing to bypass rules (EN)',
			'(?i)you\s+are\s+(now\s+)?(a\s+)?(free|liberated|unshackled|unchained)\s+(ai|assistant|model|bot)' => 'injection: persona override (EN)',
			'(?i)(switch\s+to|enter|activate|enable)\s+(your\s+)?(unrestricted|uncensored|evil|developer|jailbreak)\s+(mode|version|persona|self)' => 'injection: persona override (EN)',
			'(?i)(از\s+این\s+به\s+بعد|الان|حالا).{0,20}(هوش\s+مصنوعی\s+بدون\s+محدودیت|دستیار\s+آزاد|ربات\s+بدون\s+قانون)\s+هستی' => 'injection: persona override (FA)',
			'(?i)(وانمود\s+کن|تصور\s+کن|نقش\s+بازی\s+کن).{0,30}(قانونی\s+نداری|محدودیتی\s+نداری|فیلتری\s+نداری)' => 'injection: fictional framing to bypass rules (FA)',
			'(?i)(نقش\s+(یک|یه)\s+(هوش\s+مصنوعی\s+)?(بدون\s+(قانون|محدودیت|سانسور)|آزاد|هکر)\s+رو\s+بازی\s+کن)' => 'injection: persona override (FA)',
			'(?i)(حالا|الان)\s+(یک|یه)\s+(دستیار|هوش\s+مصنوعی)\s+(آزاد|رها|بدون\s+قانون)\s+هستی' => 'injection: persona override (FA)',
		];
	}

	/** @return array<string, string> */
	public static function systemPromptExtraction(): array
	{
		return [
			'(?i)(print|repeat|reveal|show|output|tell\s+me|what\s+is|display)\s+(your\s+)?(system\s+prompt|initial\s+prompt|original\s+instructions?|base\s+prompt|hidden\s+instructions?)' => 'injection: system prompt extraction (EN)',
			'(?i)(what\s+(were\s+you|are\s+you)\s+told|what\s+instructions\s+(were\s+you|have\s+you\s+been)\s+given)' => 'injection: system prompt extraction (EN)',
			'(?i)(share|dump|leak|expose|output)\s+(your\s+)?(full\s+)?(system\s+prompt|initial\s+instructions?|configuration|setup)' => 'injection: system prompt extraction (EN)',
			'(?i)what\s+(is\s+)?(your\s+)?(system\s+prompt|first\s+instruction|base\s+instruction|core\s+directive)' => 'injection: system prompt extraction (EN)',
			'(?i)(سیستم\s+پرامپت|دستورالعمل\s+(اولیه|اصلی|پنهان)).{0,20}(نشون\s+بده|نشان\s+بده|بگو|چیه|چی\s+هست)' => 'injection: system prompt extraction (FA)',
			'(?i)(چه\s+دستوراتی\s+(بهت\s+داده\s+شده|داری)|اول\s+از\s+همه\s+چی\s+بهت\s+گفتن)' => 'injection: system prompt extraction (FA)',
			'(?i)(پرامپت\s+(اولیه|اصلی|سیستم).{0,15}(چیه|چی\s+هست|بگو|نشون\s+بده|نشان\s+بده))' => 'injection: system prompt extraction (FA)',
		];
	}

	/** @return array<string, string> */
	public static function safetyBypass(): array
	{
		return [
			'(?i)(system\s+prompt|your\s+instructions?|your\s+rules?)\s*(:|=|-|are\s*:)' => 'injection: system prompt redefinition header (EN)',
			'(?i)(override|bypass|disable|remove|turn\s+off|circumvent)\s+(your\s+)?(safety|filter|guardrail|restriction|rule|alignment|ethical\s+guideline)' => 'injection: safety bypass (EN)',
			'(?i)do\s+(not|n\'t)\s+(follow|obey|apply|enforce)\s+(your\s+)?(rule|instruction|constraint|restriction|ethical|safety)' => 'injection: instruction rejection (EN)',
			'(?i)(jailbreak|dan\s+mode|developer\s+mode|sudo\s+mode|god\s+mode|unrestricted\s+mode|evil\s+mode|training\s+mode|maintenance\s+mode)' => 'injection: jailbreak mode keyword (EN)',
			'(?i)(ignore|skip|bypass)\s+(your\s+)?(content\s+policy|usage\s+policy|safety\s+policy|terms\s+of\s+service)' => 'injection: safety bypass (EN)',
			'(?i)there\s+(are|is)\s+no\s+(rule|restriction|limit|guideline|filter|safety).{0,20}(here|now|anymore|in\s+this\s+conversation)' => 'injection: safety bypass (EN)',
			'(?i)(فیلترها?|محدودیت|سانسور|قوانین).{0,30}(غیرفعال|دور\s+بزن|حذف\s+کن|خاموش\s+کن)' => 'injection: safety bypass (FA)',
			'(?i)(جیل.?بریک|حالت\s+(توسعه.?دهنده|بدون\s+محدودیت|هکر|خدا)|دن\s+مود)' => 'injection: jailbreak mode keyword (FA)',
			'(?i)(سیاست\s+(محتوا|امنیت|استفاده)|قوانین\s+استفاده).{0,20}(نادیده|دور\s+بزن|اعمال\s+نمی.?شه)' => 'injection: safety bypass (FA)',
		];
	}

	/** @return array<string, string> */
	public static function fakeAuthority(): array
	{
		return [
			'(?i)(anthropic|openai|your\s+(creator|developer|maker|owner)).{0,30}(says?|told\s+you|authoriz|want\s+you\s+to|instruct).{0,30}(ignore|bypass|disable|reveal)' => 'injection: fake authority claim (EN)',
			'(?i)(this\s+is\s+(a\s+)?(test|maintenance|debug|emergency)).{0,40}(disable|ignore|bypass|override)\s+(your\s+)?(safety|filter|rule)' => 'injection: fake maintenance/test claim (EN)',
			'(?i)(i\s+am|i\'m)\s+(your\s+)?(developer|creator|owner|admin|operator).{0,30}(disable|ignore|bypass|reveal|override)' => 'injection: fake authority claim (EN)',
			'(?i)(special|admin|root|superuser|elevated)\s+(access|mode|privilege).{0,30}(granted|enabled|activated)' => 'injection: fake authority claim (EN)',
			'(?i)(آنتروپیک|سازنده|توسعه.?دهنده).{0,30}(گفته|دستور\s+داده|می.?خواد).{0,30}(محدودیت|فیلتر|قانون).{0,20}(نادیده|غیرفعال)' => 'injection: fake authority claim (FA)',
			'(?i)(این\s+یک\s+(آزمایش|تست|حالت\s+اضطراری|دیباگ)\s+است).{0,40}(قوانین|فیلترها|محدودیت).{0,20}(نادیده\s+بگیر|غیرفعال\s+کن)' => 'injection: fake maintenance claim (FA)',
			'(?i)(من\s+(توسعه.?دهنده.{0,3}|ادمین|سازنده)\s+هستم|من\s+سازنده.{0,3}هستم).{0,40}(بی.?خیال|نادیده|غیرفعال|فاش\s+کن)' => 'injection: fake authority claim (FA)',
		];
	}

	/** @return array<string, string> */
	public static function encodingTricks(): array
	{
		return [
			'(?i)(decode\s+this\s+and\s+(follow|execute|run)|the\s+following\s+is\s+(base64|encoded|rot13)).{0,60}(instruction|prompt|command)' => 'injection: encoded instruction bypass (EN)',
			'(?i)(translate\s+this\s+(then\s+)?(follow|execute|do\s+what\s+it\s+says)|execute\s+the\s+following\s+after\s+translating)' => 'injection: translation bypass (EN)',
			'(?i)(the\s+)?(following|next)\s+(text|message|content)\s+is\s+(a\s+)?(hidden|secret|encoded|obfuscated)\s+(command|instruction|prompt)' => 'injection: encoded instruction bypass (EN)',
			'(?i)interpret\s+(the\s+)?(following|this)\s+(as\s+(a\s+)?(new\s+)?\w*\s*(instruction|command|rule|overriding)|overriding)' => 'injection: encoded instruction bypass (EN)',
			'(?i)(این\s+متن\s+را\s+(رمزگشایی|دیکود)\s+کن\s+(و\s+)?(اجرا|دنبال)\s+کن|دستور\s+زیر\s+رو\s+بعد\s+از\s+ترجمه\s+اجرا\s+کن)' => 'injection: encoded instruction bypass (FA)',
			'(?i)(متن\s+زیر\s+یک\s+(دستور\s+پنهان|فرمان\s+مخفی)\s+است).{0,30}(اجرا|دنبال|انجام)\s+کن' => 'injection: encoded instruction bypass (FA)',
		];
	}

	/** @return array<string, string> */
	public static function indirectInjection(): array
	{
		return [
			'(?i)(the\s+(document|file|email|webpage|article|user)\s+(says?|instructs?|tells?\s+you|wants?\s+you).{0,30}(ignore|bypass|reveal|disable))' => 'injection: indirect content injection (EN)',
			'(?i)(hidden\s+instruction|secret\s+command|invisible\s+text).{0,30}(follow|execute|obey)' => 'injection: hidden instruction claim (EN)',
			'(?i)(note\s+to\s+(ai|assistant|model)|attention\s+(ai|assistant|llm)).{0,40}(ignore|bypass|override|instead)' => 'injection: indirect content injection (EN)',
			'(?i)\[?(system|admin|override|instruction)\]?\s*:\s*(ignore|bypass|forget|disregard)' => 'injection: indirect content injection (EN)',
			'(?i)(سند|فایل|ایمیل|صفحه\s+وب).{0,20}(می.?گوید|دستور\s+می.?دهد).{0,30}(نادیده\s+بگیر|غیرفعال\s+کن|فاش\s+کن)' => 'injection: indirect content injection (FA)',
			'(?i)(توجه\s+(هوش\s+مصنوعی|دستیار|مدل)).{0,40}(نادیده|بی.?خیال|به\s+جای\s+آن)' => 'injection: indirect content injection (FA)',
		];
	}

	/** @return array<string, string> */
	public static function csam(): array
	{
		return [
			'(?i)(child\s+(porn(ography)?|sex|nude|naked|exploitation|sexual\s+abuse\s+material)|csam|lolicon|underage\s+sex)' => 'CSAM: child sexual content keyword (EN)',
			'(?i)(sex(ual)?\s+(content|image|video|photo).{0,20}(minor|child|kid|underage|year.old))' => 'CSAM: sexual content involving minor (EN)',
			'(?i)(groom|seduce|lure|gain\s+access\s+to).{0,20}(a\s+)?(child|minor|kid|underage\s+\w+)\s+(for\s+(sexual|sex|intimate))' => 'CSAM: grooming minor (EN)',
			'(?i)(sexual(ly)?\s+(exploit|abuse|assault|photograph).{0,15}(a\s+)?(child|minor|kid|underage))' => 'CSAM: child sexual exploitation (EN)',
			'(?i)(پورن|سکس|محتوای\s+(جنسی|سکسی)).{0,20}(کودک|بچه|دختربچه|پسربچه|نوجوان\s+زیر|صغیر|نابالغ)' => 'CSAM: child sexual content (FA)',
			'(?i)(تصاویر\s+جنسی\s+(کودک|بچه|خردسال|نوجوان\s+زیر\s+سن))' => 'CSAM: child sexual content (FA)',
			'(?i)(سوءاستفاده\s+جنسی\s+از\s+(کودک|بچه|صغیر)|اغوای\s+(کودک|بچه)\s+برای\s+(رابطه|تماس\s+جنسی))' => 'CSAM: child sexual exploitation (FA)',
		];
	}

	/** @return array<string, string> */
	public static function drugSynthesisAndTrafficking(): array
	{
		return [
			'(?i)(how\s+to|synthesiz|manufactur|cook|mak(e|ing)|produc).{0,40}(meth(amphetamine)?|fentanyl|heroin|crack\s+cocaine|crystal\s+meth|mdma\s+from|lsd\s+at\s+home)' => 'drugs: synthesis method request (EN)',
			'(?i)(buy|sell|ship|traffic|smuggl|deal(ing)?|sourc(e|ing)|distribut).{0,30}(drug|narcotic|heroin|meth|fentanyl|cocaine|opium|crack|marijuana\s+for\s+sale)' => 'drugs: trafficking or procurement (EN)',
			'(?i)(precursor\s+chemicals?\s+for|clandestine\s+lab\s+(setup|instructions?)|home\s+drug\s+lab)' => 'drugs: synthesis lab setup (EN)',
			'(?i)(طرز\s+(ساخت|تهیه|پخت)|روش\s+(ساختن|تولید)).{0,40}(شیشه|هرویین|متآمفتامین|فنتانیل|کراک|اکستازی|تریاک)' => 'drugs: synthesis method (FA)',
			'(?i)(آموزش\s+ساخت\s+(شیشه|مت|هرویین|کراک|تریاک|اکستازی))' => 'drugs: synthesis method (FA)',
			'(?i)(خرید\s+و\s+فروش|قاچاق|فروش|توزیع).{0,30}(شیشه|هرویین|تریاک|مواد\s+مخدر|کوکایین|حشیش\s+برای\s+فروش)' => 'drugs: trafficking (FA)',
			'(?i)(قاچاق\s+مواد\s+مخدر|دلال\s+مواد|فروش\s+مواد\s+مخدر)' => 'drugs: trafficking (FA)',
		];
	}

	/** @return array<string, string> */
	public static function illegalWeapons(): array
	{
		return [
			'(?i)(how\s+to|build|make|assemble|3d.?print|print|manufacture).{0,30}(ghost\s+gun|untraceable\s+(gun|firearm|weapon)|zip\s+gun|unregistered\s+firearm)' => 'weapons: untraceable firearm manufacturing (EN)',
			'(?i)(convert|modify|illegally\s+modify).{0,20}(pistol|rifle|gun|firearm|ar.?15|ak.?47|glock).{0,20}(to\s+(automatic|full.?auto)|full\s+auto\s+mod|auto\s+sear)' => 'weapons: illegal automatic conversion (EN)',
			'(?i)(how\s+to|make|build|assemble|construct).{0,30}(pipe\s+bomb|ied|improvised\s+explosive|homemade\s+bomb|nail\s+bomb|pressure\s+cooker\s+bomb)' => 'weapons: IED or bomb construction (EN)',
			'(?i)(how\s+to|synthesiz|mak(e|ing)|produc).{0,40}(sarin|vx\s+nerve|mustard\s+gas|nerve\s+agent|chemical\s+weapon|novichok)' => 'weapons: chemical weapon synthesis (EN)',
			'(?i)(buy|purchase|acquire)\s+(a\s+)?(gun|firearm|weapon)\s+(without|bypassing).{0,20}(background\s+check|serial\s+number|registration|license)' => 'weapons: illegal firearm acquisition (EN)',
			'(?i)(ساخت|تهیه|خرید).{0,30}(اسلحه\s+غیرقانونی|سلاح\s+بدون\s+مجوز|تفنگ\s+دست\s+ساز|چاقوی\s+ضامن.?دار|اسلحه\s+خانگی)' => 'weapons: illegal weapon manufacturing (FA)',
			'(?i)(ساخت|تهیه).{0,30}(بمب\s+(خانگی|دست.?ساز)|مواد\s+منفجره\s+(خانگی|دست.?ساز)|نارنجک\s+دست.?ساز)' => 'weapons: homemade explosive (FA)',
			'(?i)(ساخت\s+بمب\s+دست.?ساز|بمب\s+خانگی\s+ساخت)' => 'weapons: homemade explosive (FA)',
			'(?i)(سلاح\s+شیمیایی|گاز\s+(خردل|سارین|اعصاب)|عامل\s+شیمیایی\s+(کشنده|جنگی)|سلاح\s+بیولوژیک)' => 'weapons: chemical weapon (FA)',
		];
	}

	/** @return array<string, string> */
	public static function cybercrime(): array
	{
		return [
			'(?i)(how\s+to|write|create|build|code|develop).{0,30}(ransomware|keylogger|trojan|rootkit|worm|botnet|rat\s+(malware|tool)|spyware)' => 'cybercrime: malware creation (EN)',
			'(?i)(hack\s+into|break\s+into|gain\s+(unauthorized\s+)?access\s+to).{0,30}(someone.{0,10}account|bank\s+account|email\s+account|wifi\s+password|corporate\s+network)' => 'cybercrime: unauthorised account access (EN)',
			'(?i)(phishing\s+(kit|page|template)|fake\s+(login|bank)\s+page|credential\s+harvest(ing)?|spear.?phish)' => 'cybercrime: phishing kit (EN)',
			'(?i)(carding|stolen\s+credit\s+card|cc\s+dump|fullz\s+for\s+sale|cvv\s+shop|card\s+skimmer)' => 'cybercrime: payment card fraud (EN)',
			'(?i)(ddos\s+attack|launch\s+(a\s+)?ddos|how\s+to\s+ddos|botnet\s+for\s+hire|stress(er|test)\s+service\s+(to\s+)?attack)' => 'cybercrime: DDoS attack (EN)',
			'(?i)(هک\s+کردن|نفوذ\s+به).{0,30}(حساب|ایمیل|پسورد|رمز\s+عبور|شبکه\s+(وای.?فای|وایرلس)|سیستم)' => 'cybercrime: unauthorised access (FA)',
			'(?i)(ساخت|نوشتن|ایجاد).{0,30}(ویروس|بدافزار|باج.?افزار|تروجان|کی.?لاگر|روت.?کیت)' => 'cybercrime: malware creation (FA)',
			'(?i)(صفحه\s+جعلی|فیشینگ|سرقت\s+اطلاعات\s+(کاربر|بانکی|کارت)|کیت\s+فیشینگ)' => 'cybercrime: phishing (FA)',
			'(?i)(آموزش\s+هک\s+کردن\s+(اکانت|حساب|سیستم|شبکه))' => 'cybercrime: unauthorised access (FA)',
			'(?i)(حمله\s+(دیداس|ddos)|چطور\s+(سایت|سرور)\s+رو\s+داون\s+کنم)' => 'cybercrime: DDoS attack (FA)',
		];
	}

	/** @return array<string, string> */
	public static function piracyAndCracking(): array
	{
		return [
			'(?i)(crack(ing)?|keygen|serial\s+key|license\s+bypass|bypass\s+drm|pirat(e|ing)\s+software|nulled\s+(script|plugin|theme|software))' => 'piracy: software cracking or DRM bypass (EN)',
			'(?i)(torrent.{0,20}(full\s+version|cracked|patched)|warez\s+site|download\s+cracked\s+software)' => 'piracy: warez and torrents (EN)',
			'(?i)(کرک\s+(نرم.?افزار|بازی)|سریال\s+کی\s+(رایگان|دانلود)|دور\s+زدن\s+لایسنس|نسخه\s+کرک.?شده)' => 'piracy: software cracking (FA)',
			'(?i)(کرک\s+نرم.?افزار\s+\w+|کرک\s+(ادوبی|آفیس|ویندوز|فتوشاپ|اتوکد))' => 'piracy: software cracking (FA)',
			'(?i)(دانلود\s+(رایگان\s+)?(غیرقانونی|کرک\s+شده|بدون\s+لایسنس)\s+(نرم.?افزار|بازی))' => 'piracy: illegal download (FA)',
			'(?i)(دانلود.{0,15}(غیرقانونی.{0,10}کرک|کرک.{0,10}غیرقانونی).{0,20}(نرم.?افزار|بازی))' => 'piracy: illegal download (FA)',
		];
	}

	/** @return array<string, string> */
	public static function financialCrimes(): array
	{
		return [
			'(?i)(money\s+launder(ing)?|launder\s+money\s+through|structuring\s+cash\s+deposits|smurfing.{0,20}bank|evad(e|ing)\s+(tax|sanctions|reporting))' => 'financial-crime: money laundering or sanctions evasion (EN)',
			'(?i)(launder\s+money|money\s+laundering).{0,30}(shell\s+compan|offshore|cryptocurrency|crypto|account)' => 'financial-crime: money laundering or sanctions evasion (EN)',
			'(?i)(fake|forge|counterfeit|falsify).{0,20}(passport|id\s+card|driver.?s\s+license|document|certificate|banknote|check|cheque)' => 'financial-crime: document forgery (EN)',
			'(?i)(fake\s+(invoice|receipt|bank\s+statement)|forge.{0,20}(check|cheque|document|signature).{0,20}(bank|financial|government))' => 'financial-crime: document forgery (EN)',
			'(?i)(pump\s+and\s+dump|insider\s+trading|market\s+manipulation|ponzi\s+scheme|pyramid\s+scheme)' => 'financial-crime: securities fraud (EN)',
			'(?i)(پولشویی|فرار\s+از\s+(مالیات|تحریم)|انتقال\s+غیرقانونی\s+پول|پنهان\s+کردن\s+درآمد)' => 'financial-crime: money laundering or sanctions evasion (FA)',
			'(?i)(جعل\s+(اسناد|چک|قرارداد|امضا|پاسپورت|شناسنامه)|صدور\s+فاکتور\s+جعلی)' => 'financial-crime: document forgery (FA)',
			'(?i)(پولشویی\s+از\s+طریق|پنهان\s+کردن\s+پول.{0,20}(حساب|ارز\s+دیجیتال|کریپتو))' => 'financial-crime: money laundering or sanctions evasion (FA)',
			'(?i)(دستکاری\s+بازار\s+(بورس|سهام|ارز)|طرح\s+(هرمی|پانزی)|معاملات\s+با\s+اطلاعات\s+محرمانه)' => 'financial-crime: securities fraud (FA)',
		];
	}

	/** @return array<string, string> */
	public static function humanTrafficking(): array
	{
		return [
			'(?i)(human\s+trafficking|sex\s+trafficking|labor\s+trafficking|smuggl.{0,20}(people|migrants|refugees|workers).{0,20}(border|for\s+money|illegally))' => 'trafficking: human smuggling or exploitation (EN)',
			'(?i)(traffic\s+people|traffick(ing)?\s+people|smuggle\s+people).{0,30}(border|money|profit|covertly)' => 'trafficking: human smuggling or exploitation (EN)',
			'(?i)(recruit|lure|trick|force).{0,30}(women|girls?|children|migrants).{0,30}(into\s+(prostitution|sex\s+work|slavery|forced\s+labor))' => 'trafficking: forced labour or sex work (EN)',
			'(?i)(sell\s+(people|humans?|women|children)|buy\s+(a\s+)?(slave\s*(person)?|person|woman|child)\s+online)' => 'trafficking: human sale (EN)',
			'(?i)(قاچاق\s+انسان|قاچاق\s+دختر|قاچاق\s+زن|قاچاق\s+کارگر|فروش\s+انسان)' => 'trafficking: human trafficking (FA)',
			'(?i)(recruit|lure|trick|coerce).{0,20}(women|girls?|people|migrants).{0,20}into\s+(forced\s+)?(prostitution|sex\s+work|slavery|trafficking)' => 'trafficking: forced labour or sex work (EN)',
			'(?i)(قاچاق\s+دختران\s+به\s+خارج|انتقال\s+غیرقانونی\s+(زنان|کودکان|دختران))' => 'trafficking: human trafficking (FA)',
			'(?i)(مجبور\s+کردن.{0,20}به\s+(تن.?فروشی|روسپی.?گری|کار\s+اجباری|بردگی\s+جنسی))' => 'trafficking: forced labour or sex work (FA)',
			'(?i)(خرید\s+و\s+فروش\s+(انسان|دختر|زن)|برده.?داری\s+(جنسی|مدرن))' => 'trafficking: human sale (FA)',
		];
	}

	/** @return array<string, string> */
	public static function terrorismAndMassViolence(): array
	{
		return [
			'(?i)(how\s+to|plan|execute|carry\s+out|coordinate|orchestrate).{0,40}(terrorist\s+attack|mass\s+shooting|suicide\s+bombing|car\s+ramming|school\s+shooting)' => 'terrorism: attack planning (EN)',
			'(?i)(recruit(ing|ment)?.{0,30}(for\s+(isis|isil|al.?qaeda|daesh|jihad|hamas|hezbollah))|join\s+(isis|isil|al.?qaeda|daesh|hamas))' => 'terrorism: extremist recruitment (EN)',
			'(?i)(radicali(z|s)(e|ing|ation)|white\s+supremac(y|ist)|far.?right\s+terrorist|eco.?terrorist\s+attack)' => 'terrorism: radicalization (EN)',
			'(?i)(manifesto\s+(for|to)\s+(attack|kill|shoot|bomb|inspire)|inspire\s+(others?\s+to\s+)?attack|(write|create|post)\s+a\s+manifesto\s+to\s+inspire.{0,30}(attack|violence|kill))' => 'terrorism: incitement (EN)',
			'(?i)(برنامه.?ریزی|اجرای|حمله).{0,40}(تروریستی|انتحاری|بمب.?گذاری|کشتار\s+جمعی|تیراندازی\s+دسته.?جمعی)' => 'terrorism: attack planning (FA)',
			'(?i)(جذب\s+نیرو|عضوگیری|جذب\s+عضو).{0,30}(داعش|القاعده|طالبان|جهاد\s+مسلحانه|گروه\s+تروریستی)' => 'terrorism: extremist recruitment (FA)',
			'(?i)(حمله\s+تروریستی\s+به\s+تجمع|برنامه.?ریزی\s+برای\s+(انفجار|کشتار))' => 'terrorism: attack planning (FA)',
			'(?i)(افراطی.?گری|رادیکالیسم\s+(اسلامی|مسلح)|سلفی.?گری\s+جهادی)' => 'terrorism: radicalization (FA)',
		];
	}

	/** @return array<string, string> */
	public static function unethicalAll(): array
	{
		return array_merge(
			self::selfHarm(),
			self::harassment(),
			self::manipulation(),
			self::blackmail(),
			self::stalking(),
			self::nonConsensual(),
			self::hateSpeech(),
		);
	}

	/** @return array<string, string> */
	public static function promptInjectionAll(): array
	{
		return array_merge(
			self::instructionSuppression(),
			self::personaOverride(),
			self::systemPromptExtraction(),
			self::safetyBypass(),
			self::fakeAuthority(),
			self::encodingTricks(),
			self::indirectInjection(),
		);
	}

	/** @return array<string, string> */
	public static function illegalAll(): array
	{
		return array_merge(
			self::csam(),
			self::drugSynthesisAndTrafficking(),
			self::illegalWeapons(),
			self::cybercrime(),
			self::piracyAndCracking(),
			self::financialCrimes(),
			self::humanTrafficking(),
			self::terrorismAndMassViolence(),
		);
	}

	/** @return array<string, string> */
	public static function all(): array
	{
		return array_merge(self::illegalAll() + self::promptInjectionAll(), self::unethicalAll());
	}
}
