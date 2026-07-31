<?php

/**
 * Complete99 nutrition and food-information guide seeds.
 *
 * Schema contract:
 *
 * - This file contains editorial seeds, not approved medical advice.
 * - Every guide is draft and noindex by default.
 * - A guide may be published only after all review requirements for that
 *   record have been completed and recorded by the publishing workflow.
 * - Source IDs resolve to the authoritative URL registry in this file.
 * - Related dish and ingredient codes express editorial relationships only.
 *   They do not prove that a dish has a nutrient, allergen or dietary trait.
 * - A change to a recipe, ingredient specification, supplier declaration,
 *   portion or preparation method requires a new review of affected claims.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'schema'                    => 'complete99-nutrition-guide-seeds/v1',
	'registry_reviewed_at'      => '2026-07-31',
	'default_public_status'     => 'draft',
	'default_internal_status'   => 'editorial_reference',
	'default_index_policy'      => 'noindex',
	'publish_by_default'        => false,
	'source_registry'           => array(
		'israel-food-labeling' => array(
			'title'     => 'Food Label and Nutritional Labeling',
			'authority' => 'Israel Ministry of Health',
			'type'      => 'official_regulatory_guidance',
			'url'       => 'https://www.gov.il/he/pages/food-labeling?chapterIndex=12',
		),
		'israel-nutrition-labeling-regulations' => array(
			'title'     => 'Public Health Protection Food Nutritional Labeling Regulations 2017',
			'authority' => 'Israel Ministry of Health',
			'type'      => 'official_regulation',
			'url'       => 'https://www.gov.il/he/pages/food-labeling-2017',
		),
		'israel-gluten-labeling-regulations' => array(
			'title'     => 'Israeli Gluten Labeling Regulations',
			'authority' => 'Israel Ministry of Health',
			'type'      => 'official_regulation',
			'url'       => 'https://www.gov.il/BlobFolder/legalinfo/health-mazon11a/he/files_legislation_food_health-mazon11A.pdf',
		),
		'israel-dietary-guidelines' => array(
			'title'     => 'Israel National Dietary Guidelines and Food Rainbow',
			'authority' => 'Israel Ministry of Health',
			'type'      => 'official_public_health_guideline',
			'url'       => 'https://www.gov.il/he/pages/dietary-guidelines?chapterIndex=1',
		),
		'israel-dietitian-licensing' => array(
			'title'     => 'Nutrition Profession Licensing',
			'authority' => 'Israel Ministry of Health',
			'type'      => 'official_professional_licensing_guidance',
			'url'       => 'https://www.gov.il/he/pages/licensing-medical-nutrition?chapterIndex=1',
		),
		'israel-privacy-amendment-13' => array(
			'title'     => 'Privacy Protection Law Amendment 13 Questions and Answers',
			'authority' => 'Israel Privacy Protection Authority',
			'type'      => 'official_privacy_guidance',
			'url'       => 'https://www.gov.il/he/pages/tikun13_qa?chapterIndex=6',
		),
		'codex-allergen-management' => array(
			'title'     => 'Code of Practice on Food Allergen Management for Food Business Operators',
			'authority' => 'Codex Alimentarius Commission',
			'type'      => 'international_food_safety_code',
			'url'       => 'https://www.fao.org/fao-who-codexalimentarius/sh-proxy/en/?lnk=1&url=https%3A%2F%2Fworkspace.fao.org%2Fsites%2Fcodex%2FStandards%2FCXC+80-2020%2FCXC_080e.pdf',
		),
		'eu-allergen-information' => array(
			'title'     => 'Allergen Labelling in the European Union',
			'authority' => 'European Commission',
			'type'      => 'official_regulatory_guidance',
			'url'       => 'https://food.ec.europa.eu/food-safety/campaign-2026/allergies_en',
		),
		'eu-nutrition-claims' => array(
			'title'     => 'Regulation EC 1924/2006 on Nutrition and Health Claims',
			'authority' => 'European Union',
			'type'      => 'official_regulation',
			'url'       => 'https://eur-lex.europa.eu/eli/reg/2006/1924/oj',
		),
		'iso-vegetarian-vegan' => array(
			'title'     => 'ISO 23662:2021 Vegetarian and Vegan Food Definitions and Technical Criteria',
			'authority' => 'International Organization for Standardization',
			'type'      => 'international_standard',
			'url'       => 'https://www.iso.org/standard/76574.html',
		),
		'who-healthy-diet' => array(
			'title'     => 'Healthy Diet',
			'authority' => 'World Health Organization',
			'type'      => 'official_public_health_guidance',
			'url'       => 'https://www.who.int/news-room/fact-sheets/detail/healthy-diet',
		),
		'who-sodium' => array(
			'title'     => 'Sodium Reduction',
			'authority' => 'World Health Organization',
			'type'      => 'official_public_health_guidance',
			'url'       => 'https://www.who.int/news-room/fact-sheets/detail/sodium-reduction',
		),
		'usda-fooddata-central' => array(
			'title'     => 'FoodData Central API Guide',
			'authority' => 'United States Department of Agriculture',
			'type'      => 'official_nutrient_database',
			'url'       => 'https://fdc.nal.usda.gov/api-guide/',
		),
		'usda-retention-factors' => array(
			'title'     => 'USDA Table of Nutrient Retention Factors',
			'authority' => 'United States Department of Agriculture',
			'type'      => 'official_technical_reference',
			'url'       => 'https://www.ars.usda.gov/arsuserfiles/80400530/pdf/retn06.pdf',
		),
		'lancet-carbohydrate-quality' => array(
			'title'     => 'Carbohydrate Quality and Human Health: Systematic Reviews and Meta-analyses',
			'authority' => 'The Lancet via PubMed',
			'type'      => 'systematic_review_and_meta_analysis',
			'url'       => 'https://pubmed.ncbi.nlm.nih.gov/30638909/',
		),
		'plant-based-nutrient-status-review' => array(
			'title'     => 'Nutrient Intake and Status in Adults Consuming Plant-based Diets',
			'authority' => 'Nutrients via PubMed',
			'type'      => 'systematic_review',
			'url'       => 'https://pubmed.ncbi.nlm.nih.gov/35010904/',
		),
		'vegan-adequacy-review' => array(
			'title'     => 'Intake and Adequacy of the Vegan Diet',
			'authority' => 'Clinical Nutrition via PubMed',
			'type'      => 'systematic_review',
			'url'       => 'https://pubmed.ncbi.nlm.nih.gov/33341313/',
		),
		'predimed-trial' => array(
			'title'     => 'Primary Prevention of Cardiovascular Disease with a Mediterranean Diet',
			'authority' => 'New England Journal of Medicine via PubMed',
			'type'      => 'randomized_controlled_trial',
			'url'       => 'https://pubmed.ncbi.nlm.nih.gov/29897866/',
		),
		'cochrane-mediterranean-review' => array(
			'title'     => 'Mediterranean-style Diet for Cardiovascular Disease Prevention',
			'authority' => 'Cochrane Database of Systematic Reviews via PubMed',
			'type'      => 'systematic_review',
			'url'       => 'https://pubmed.ncbi.nlm.nih.gov/30864165/',
		),
		'dash-trial' => array(
			'title'     => 'A Clinical Trial of the Effects of Dietary Patterns on Blood Pressure',
			'authority' => 'New England Journal of Medicine via PubMed',
			'type'      => 'randomized_controlled_feeding_trial',
			'url'       => 'https://pubmed.ncbi.nlm.nih.gov/9099655/',
		),
		'dash-sodium-trial' => array(
			'title'     => 'Effects of Diet and Sodium Intake on Blood Pressure',
			'authority' => 'DASH-Sodium Trial via PubMed',
			'type'      => 'randomized_controlled_feeding_trial',
			'url'       => 'https://pubmed.ncbi.nlm.nih.gov/11747380/',
		),
		'acg-celiac-guideline' => array(
			'title'     => 'American College of Gastroenterology Guideline for Celiac Disease',
			'authority' => 'American College of Gastroenterology via PubMed',
			'type'      => 'clinical_guideline',
			'url'       => 'https://pubmed.ncbi.nlm.nih.gov/36602836/',
		),
	),
	'guides'                    => array(
		array(
			'id'                         => 'nutrition-guide-calculation-method',
			'slug'                       => array(
				'he' => 'how-nutrition-values-are-calculated',
				'en' => 'en-how-nutrition-values-are-calculated',
			),
			'title'                      => array(
				'he' => 'איך מחשבים ערכים תזונתיים של מנה',
				'en' => 'How a dish nutrition estimate is calculated',
			),
			'short_answer'               => array(
				'he' => 'חישוב אמין מתחיל במתכון שקול ובמשקל המנה המוגשת. הוא מביא בחשבון את מקור הנתונים לכל מרכיב, שינויי משקל בבישול ומקדמי שימור, ומציג בבירור אם הערך מחושב או נבדק במעבדה.',
				'en' => 'A reliable calculation starts with a weighed recipe and the weight of the served portion. It accounts for each ingredient data source, cooking yield and nutrient retention, and clearly states whether the value is calculated or laboratory verified.',
			),
			'evidence_scope'             => array(
				'he' => 'המקורות מסבירים מאגרי הרכב מזון, חישוב מתכונים, תשואת בישול ושימור רכיבים תזונתיים. הם תומכים בשיטת חישוב, לא בערכים של מנה מסוימת לפני שקילתה.',
				'en' => 'The sources cover food-composition databases, recipe calculation, cooking yield and nutrient retention. They support a calculation method, not the values of a particular dish before it is weighed.',
			),
			'limitations'                => array(
				'he' => 'חישוב תלוי בדיוק המתכון, בזהות מוצרי הספק, במשקל לאחר בישול ובגודל המנה. שינוי ברכיב, בספק או בשיטת הכנה עשוי לשנות את התוצאה.',
				'en' => 'A calculation depends on the exact recipe, supplier products, cooked yield and portion size. A change in an ingredient, supplier or preparation method may change the result.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'שוקלים את הסיר לפני ואחרי הבישול, רושמים את מספר המנות ושומרים את גרסת המתכון. בעמוד המנה מציגים ערך למנה ול-100 גרם יחד עם שיטת החישוב ותאריך הבדיקה.',
				'en' => 'Weigh the batch before and after cooking, record the number of portions and preserve the recipe version. The dish page can then show values per portion and per 100 grams with the method and review date.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'editorial_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => false,
				'israeli_food_law_review'  => true,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => true,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-shakshuka',
				'dish-kubeh-beet-soup',
				'dish-yemenite-beef-soup',
			),
			'related_ingredient_codes'   => array(
				'ingredient-egg',
				'ingredient-aubergine',
				'ingredient-tahini',
				'ingredient-beef',
			),
			'source_ids'                 => array(
				'israel-nutrition-labeling-regulations',
				'usda-fooddata-central',
				'usda-retention-factors',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-allergens-cross-contact',
			'slug'                       => array(
				'he' => 'food-allergens-and-cross-contact',
				'en' => 'en-food-allergens-and-cross-contact',
			),
			'title'                      => array(
				'he' => 'אלרגנים במזון ומגע צולב במטבח',
				'en' => 'Food allergens and cross-contact in the kitchen',
			),
			'short_answer'               => array(
				'he' => 'אלרגן יכול להיות חלק מכוון מהמתכון או להגיע בטעות במגע צולב. מידע אמין דורש רשימת רכיבים מלאה, הצהרות ספקים, הערכת סיכון ובקרות מטבח, ולא רק מבט בשם המנה.',
				'en' => 'An allergen may be an intentional recipe ingredient or enter unintentionally through cross-contact. Reliable information requires a complete ingredient list, supplier declarations, risk assessment and kitchen controls, not an assumption based on the dish name.',
			),
			'evidence_scope'             => array(
				'he' => 'המקורות מגדירים אלרגנים המחייבים תשומת לב ומציגים עקרונות למניעת מגע צולב, ניקוי, אחסון, ספקים והעברת מידע.',
				'en' => 'The sources identify allergens that require attention and describe principles for cross-contact prevention, cleaning, storage, supplier controls and communication.',
			),
			'limitations'                => array(
				'he' => 'רשימת מרכיבים אינה לבדה הוכחה להיעדר אלרגן. גם אזהרת "עלול להכיל" צריכה להישען על הערכת סיכון ולא להחליף בקרות ייצור ומטבח.',
				'en' => 'An ingredient list alone does not prove that an allergen is absent. A precautionary "may contain" statement should be based on risk assessment and must not replace production and kitchen controls.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'לכל מנה נשמרים בנפרד "מכיל", "עלול להכיל", "לא הוסף במתכון" ו"נבדק כנקי". שאלת אלרגיה בהזמנה עוברת לאישור אנושי ולא להחלטה אוטומטית.',
				'en' => 'Each dish separately records "contains", "may contain", "not intentionally used" and "verified free from". An allergy request in an order requires human confirmation rather than an automatic decision.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'food_safety_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => true,
				'israeli_food_law_review'  => true,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => true,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-shakshuka',
				'dish-couscous-beef',
				'dish-beef-meatballs-gravy',
			),
			'related_ingredient_codes'   => array(
				'ingredient-sesame-tahini',
				'ingredient-egg',
				'ingredient-wheat',
				'ingredient-fish',
				'ingredient-milk',
				'ingredient-mustard',
			),
			'source_ids'                 => array(
				'israel-food-labeling',
				'codex-allergen-management',
				'eu-allergen-information',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-gluten-free',
			'slug'                       => array(
				'he' => 'what-gluten-free-means',
				'en' => 'en-what-gluten-free-means',
			),
			'title'                      => array(
				'he' => 'מה פירוש "ללא גלוטן"',
				'en' => 'What "gluten-free" means',
			),
			'short_answer'               => array(
				'he' => 'היעדר קמח במתכון אינו מספיק כדי להגדיר מנה "ללא גלוטן". נדרשים רכיבים מתאימים, תנאי ייצור נאותים ובקרות שמבטיחות שהמוצר עומד ברף הגלוטן הקבוע בדין.',
				'en' => 'The absence of flour from a recipe is not enough to call a dish "gluten-free". Suitable ingredients, good production conditions and controls are needed to ensure that the food meets the legal gluten threshold.',
			),
			'evidence_scope'             => array(
				'he' => 'תקנות סימון הגלוטן בישראל קובעות תנאים לשימוש בטענה "ללא גלוטן". קו ההנחיה הקליני מסביר מדוע אנשים עם צליאק זקוקים להימנעות קפדנית ולמעקב רפואי.',
				'en' => 'Israeli gluten-labelling regulations set conditions for a "gluten-free" claim. The clinical guideline explains why people with celiac disease require strict avoidance and medical follow-up.',
			),
			'limitations'                => array(
				'he' => 'המאמר אינו מאבחן צליאק ואינו קובע שמנה מסוימת בטוחה. התאמה של מנה תלויה בגרסת המתכון, בספקים, בציוד, בניקוי ובמגע צולב בזמן ההכנה.',
				'en' => 'The guide does not diagnose celiac disease or declare a particular dish safe. Dish suitability depends on the recipe version, suppliers, equipment, cleaning and cross-contact during preparation.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'מנה תופיע במסנן "ללא גלוטן" רק לאחר אימות מסמכים ובקרות. מנה שאין בה רכיב גלוטן מכוון אך לא נבדקה למגע צולב תוצג כ"לא אומתה כמנה ללא גלוטן".',
				'en' => 'A dish appears in a "gluten-free" filter only after document and control verification. A dish without an intentional gluten ingredient that has not been assessed for cross-contact is shown as "not verified gluten-free".',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'food_safety_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => true,
				'israeli_food_law_review'  => true,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => true,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-shakshuka',
				'dish-kubeh-beet-soup',
				'dish-couscous-beef',
			),
			'related_ingredient_codes'   => array(
				'ingredient-wheat',
				'ingredient-barley',
				'ingredient-rye',
				'ingredient-oats',
				'ingredient-pita',
				'ingredient-couscous',
			),
			'source_ids'                 => array(
				'israel-gluten-labeling-regulations',
				'codex-allergen-management',
				'acg-celiac-guideline',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-vegan-vegetarian',
			'slug'                       => array(
				'he' => 'vegan-and-vegetarian-food-labels',
				'en' => 'en-vegan-and-vegetarian-food-labels',
			),
			'title'                      => array(
				'he' => 'טבעוני, צמחוני ואלרגנים: מה ההבדל',
				'en' => 'Vegan, vegetarian and allergen information',
			),
			'short_answer'               => array(
				'he' => 'טבעוני וצמחוני מתארים את הרכיבים ואת אופן הייצור לפי הגדרה שנבחרה. הם אינם הבטחה שהמזון נקי מחלב, ביצים או אלרגן אחר שנמצא בסביבת הייצור.',
				'en' => 'Vegan and vegetarian describe ingredients and production according to a defined standard. They do not guarantee that food is free from milk, egg or another allergen present in the production environment.',
			),
			'evidence_scope'             => array(
				'he' => 'תקן ISO מציג הגדרות וקריטריונים למזון טבעוני וצמחוני. סקירות תזונתיות בוחנות דפוסי אכילה שלמים ולא מוכיחות שמנה בודדת מספקת תזונה מאוזנת.',
				'en' => 'The ISO standard provides definitions and technical criteria for vegan and vegetarian foods. Nutrition reviews assess whole dietary patterns and do not show that a single dish provides a balanced diet.',
			),
			'limitations'                => array(
				'he' => 'הרכב תזונתי משתנה מאוד בין מנות טבעוניות וצמחוניות. התג אינו מעיד לבדו על חלבון, B12, ברזל, סידן, יוד, נתרן, סוכר או צפיפות אנרגטית.',
				'en' => 'Nutrient composition varies widely among vegan and vegetarian dishes. The label alone says nothing definite about protein, vitamin B12, iron, calcium, iodine, sodium, sugars or energy density.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'המסנן בודק את כל רכיבי המתכון וחומרי העזר. מידע על אלרגנים מופיע בנפרד, ובמנה טבעונית לעולם אין להסתיר אזהרת חלב או ביצים הנובעת מסיכון מגע צולב.',
				'en' => 'The filter checks all recipe ingredients and processing aids. Allergen information is shown separately, and a vegan dish must never hide a milk or egg warning arising from cross-contact risk.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'editorial_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => true,
				'israeli_food_law_review'  => true,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => true,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-shakshuka',
			),
			'related_ingredient_codes'   => array(
				'ingredient-egg',
				'ingredient-milk',
				'ingredient-legumes',
				'ingredient-sesame-tahini',
				'ingredient-vegetables',
			),
			'source_ids'                 => array(
				'iso-vegetarian-vegan',
				'codex-allergen-management',
				'plant-based-nutrient-status-review',
				'vegan-adequacy-review',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-protein',
			'slug'                       => array(
				'he' => 'protein-in-a-meal',
				'en' => 'en-protein-in-a-meal',
			),
			'title'                      => array(
				'he' => 'חלבון במנה: מספרים לפני סיסמאות',
				'en' => 'Protein in a meal: values before claims',
			),
			'short_answer'               => array(
				'he' => 'כמות חלבון אמינה מוצגת בגרמים למנה ול-100 גרם לאחר חישוב המתכון והמנה המוגשת. הביטויים "מקור לחלבון" ו"עתיר חלבון" הם טענות תזונתיות שדורשות בדיקת תנאי הסף החלים.',
				'en' => 'A reliable protein amount is shown in grams per portion and per 100 grams after calculating the recipe and served portion. "Source of protein" and "high protein" are nutrition claims that require verification against the applicable conditions.',
			),
			'evidence_scope'             => array(
				'he' => 'המקורות תומכים בהצגת הרכב מזון כמותי ובבדיקה מסודרת של טענות תזונתיות. סקירות על דפוסים צמחיים מדגישות שהרכב הכולל חשוב יותר מתווית הדיאטה בלבד.',
				'en' => 'The sources support quantitative food-composition reporting and structured verification of nutrition claims. Reviews of plant-based patterns show why overall composition matters more than a dietary label alone.',
			),
			'limitations'                => array(
				'he' => 'כמות החלבון במנה אינה קובעת לבדה את צורכי האדם, איכות התזונה או התאמה למצב רפואי. צרכים משתנים לפי גיל, גוף, פעילות ומצב בריאות.',
				'en' => 'The protein amount in a dish does not by itself determine a person’s needs, diet quality or medical suitability. Requirements vary with age, body size, activity and health status.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'מציגים גרמים בפועל ומקשרים למקור החלבון במנה, כגון ביצה, דג, עוף, בשר או קטניות. אין badge חיובי עד שהחישוב ותנאי הטענה עברו אישור.',
				'en' => 'Show the measured or calculated grams and identify the dish sources, such as egg, fish, chicken, meat or pulses. No positive claim badge appears until the calculation and claim conditions are approved.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'editorial_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => false,
				'israeli_food_law_review'  => true,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => true,
			),
			'related_dish_codes'         => array(
				'dish-shakshuka',
				'dish-beef-meatballs-gravy',
				'dish-couscous-beef',
				'dish-yemenite-beef-soup',
			),
			'related_ingredient_codes'   => array(
				'ingredient-egg',
				'ingredient-fish',
				'ingredient-chicken',
				'ingredient-beef',
				'ingredient-legumes',
			),
			'source_ids'                 => array(
				'israel-nutrition-labeling-regulations',
				'eu-nutrition-claims',
				'usda-fooddata-central',
				'plant-based-nutrient-status-review',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-fibre',
			'slug'                       => array(
				'he' => 'dietary-fibre-in-food',
				'en' => 'en-dietary-fibre-in-food',
			),
			'title'                      => array(
				'he' => 'סיבים תזונתיים במזון',
				'en' => 'Dietary fibre in food',
			),
			'short_answer'               => array(
				'he' => 'סיבים מצויים בין היתר בקטניות, ירקות, פירות ודגנים מלאים. כמותם במנה צריכה להיקבע לפי רכיבים, משקלים ותשואת הבישול, ולא לפי מראה המנה.',
				'en' => 'Dietary fibre is found in foods including pulses, vegetables, fruit and whole grains. Its amount in a dish should be calculated from ingredients, weights and cooking yield, not inferred from appearance.',
			),
			'evidence_scope'             => array(
				'he' => 'הנחיות רשמיות ממליצות על מגוון מזונות צמחיים עשירים בסיבים. סקירות שיטתיות מצאו קשרים ותוצאות ניסוי שתומכים בחשיבות איכות הפחמימות וסיבים בדפוס האכילה.',
				'en' => 'Official guidance recommends varied plant foods that provide fibre. Systematic reviews report associations and trial findings that support the importance of carbohydrate quality and fibre within the dietary pattern.',
			),
			'limitations'                => array(
				'he' => 'ממצאים על דפוס אכילה אינם הוכחה שמנה אחת מונעת מחלה. טענות כמו "מקור לסיבים" או "עשיר בסיבים" דורשות חישוב ותנאי סף מתאימים.',
				'en' => 'Evidence about dietary patterns does not prove that one dish prevents disease. Claims such as "source of fibre" or "high fibre" require calculation and the applicable threshold.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'מציגים את כמות הסיבים למנה ומסבירים אילו רכיבים תורמים לה. אפשר להציע מיון לפי הכמות המאומתת, בלי לכנות מנה "טובה לעיכול" או להבטיח תוצאה רפואית.',
				'en' => 'Show fibre grams per portion and explain which ingredients contribute them. Dishes may be sorted by a verified amount without calling a dish "good for digestion" or promising a medical result.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'editorial_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => false,
				'israeli_food_law_review'  => true,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => true,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-kubeh-beet-soup',
				'dish-couscous-beef',
			),
			'related_ingredient_codes'   => array(
				'ingredient-legumes',
				'ingredient-vegetables',
				'ingredient-whole-grains',
				'ingredient-aubergine',
			),
			'source_ids'                 => array(
				'israel-dietary-guidelines',
				'who-healthy-diet',
				'lancet-carbohydrate-quality',
				'eu-nutrition-claims',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-sodium',
			'slug'                       => array(
				'he' => 'sodium-and-salt-in-a-meal',
				'en' => 'en-sodium-and-salt-in-a-meal',
			),
			'title'                      => array(
				'he' => 'נתרן ומלח במנה',
				'en' => 'Sodium and salt in a meal',
			),
			'short_answer'               => array(
				'he' => 'נתרן יכול להגיע ממלח, רטבים, תערובות תיבול, חמוצים ומוצרים מוכנים. הדרך הישירה לעזור לקורא להשוות היא להציג מיליגרם נתרן למנה ול-100 גרם על בסיס מתכון מאומת.',
				'en' => 'Sodium may come from salt, sauces, seasoning blends, pickles and prepared ingredients. The direct way to help a reader compare dishes is to show milligrams per portion and per 100 grams from a verified recipe.',
			),
			'evidence_scope'             => array(
				'he' => 'המקורות הרשמיים מציגים ערכי ייחוס לאוכלוסייה ועקרונות להפחתת נתרן. ניסויי DASH בדקו דפוסי אכילה ורמות נתרן בתנאים מבוקרים.',
				'en' => 'Official sources provide population reference values and sodium-reduction principles. DASH trials studied dietary patterns and sodium levels under controlled conditions.',
			),
			'limitations'                => array(
				'he' => 'ערך של מנה אחת אינו ייעוץ אישי ואינו קובע התאמה ליתר לחץ דם, מחלת כליה או תרופה. גם הטענה "דל נתרן" כפופה לתנאים רגולטוריים.',
				'en' => 'The value of one dish is not personal advice and does not determine suitability for hypertension, kidney disease or medication. A "low sodium" claim is also subject to regulatory conditions.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'שוקלים מלח ורכיבים מלוחים בכל גרסת מתכון, שומרים את תוויות הספק ומציגים מספר ניטרלי. שינוי ברוטב, אבקה או חמוצים מפעיל חישוב מחדש.',
				'en' => 'Weigh salt and salty ingredients in every recipe version, preserve supplier labels and show a neutral number. A change in sauce, seasoning or pickles triggers recalculation.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'editorial_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => false,
				'israeli_food_law_review'  => true,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => true,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-kubeh-beet-soup',
				'dish-yemenite-beef-soup',
				'dish-beef-meatballs-gravy',
			),
			'related_ingredient_codes'   => array(
				'ingredient-salt',
				'ingredient-pickles',
				'ingredient-amba',
				'ingredient-seasoning-blend',
				'ingredient-sauce',
			),
			'source_ids'                 => array(
				'israel-nutrition-labeling-regulations',
				'who-sodium',
				'dash-trial',
				'dash-sodium-trial',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-mediterranean-pattern',
			'slug'                       => array(
				'he' => 'mediterranean-eating-pattern',
				'en' => 'en-mediterranean-eating-pattern',
			),
			'title'                      => array(
				'he' => 'דפוס אכילה ים תיכוני ומטבח מקומי',
				'en' => 'A Mediterranean eating pattern and local cooking',
			),
			'short_answer'               => array(
				'he' => 'דפוס אכילה ים תיכוני מתאר את התמונה הכוללת לאורך זמן: מגוון ירקות, פירות, קטניות, דגנים, אגוזים ושמנים צמחיים, לצד בחירות נוספות ובהתאם להנחיות המקומיות. הוא אינו חותמת בריאות על מנה בודדת.',
				'en' => 'A Mediterranean eating pattern describes the overall picture over time: varied vegetables, fruit, pulses, grains, nuts and plant oils, alongside other choices under local guidance. It is not a health seal for a single dish.',
			),
			'evidence_scope'             => array(
				'he' => 'הנחיות משרד הבריאות בישראל מבוססות על עקרונות ים תיכוניים. מחקר PREDIMED וסקירת Cochrane מספקים ראיות באוכלוסיות ובתוצאות מוגדרות, עם מגבלות שיש להסביר לקורא.',
				'en' => 'Israel’s national guidance draws on Mediterranean principles. The PREDIMED trial and a Cochrane review provide evidence for defined populations and outcomes, with limitations that should be explained to readers.',
			),
			'limitations'                => array(
				'he' => 'אין הגדרה יחידה לכל גרסאות הדפוס הים תיכוני, ותוצאות מחקר באוכלוסייה בסיכון אינן חלות אוטומטית על כל אדם. אין להסיק שמנה מקומית מטפלת או מונעת מחלה.',
				'en' => 'There is no single definition covering every Mediterranean pattern, and findings in a high-risk population do not automatically apply to everyone. A local dish must not be presented as treating or preventing disease.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'המאמר יכול לקשר בין קטניות, ירקות, טחינה, דגנים ושיטות בישול לבין דפוס הארוחה. תג למנה יתאר רכיב או שיטת הכנה מאומתים, לא "מנה ים תיכונית בריאה".',
				'en' => 'The guide can connect pulses, vegetables, tahini, grains and cooking methods to the meal pattern. A dish badge should describe a verified ingredient or method, not call it a "healthy Mediterranean dish".',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'editorial_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => false,
				'israeli_food_law_review'  => false,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => false,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-shakshuka',
				'dish-kubeh-beet-soup',
				'dish-couscous-beef',
			),
			'related_ingredient_codes'   => array(
				'ingredient-legumes',
				'ingredient-vegetables',
				'ingredient-sesame-tahini',
				'ingredient-olive-oil',
				'ingredient-whole-grains',
			),
			'source_ids'                 => array(
				'israel-dietary-guidelines',
				'who-healthy-diet',
				'predimed-trial',
				'cochrane-mediterranean-review',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-portions-balance',
			'slug'                       => array(
				'he' => 'portion-size-and-meal-balance',
				'en' => 'en-portion-size-and-meal-balance',
			),
			'title'                      => array(
				'he' => 'גודל מנה, מגוון ואיזון בארוחה',
				'en' => 'Portion size, variety and meal balance',
			),
			'short_answer'               => array(
				'he' => 'גודל מנה עוזר להבין כמה מזון וערכים תזונתיים מתקבלים בפועל. ארוחה מגוונת נבחנת לפי השילוב בין רכיבים וקבוצות מזון, ולא לפי מספר אחד או badge יחיד.',
				'en' => 'Portion size helps explain how much food and nutrition a person actually receives. A varied meal is assessed through the combination of ingredients and food groups, not one number or badge.',
			),
			'evidence_scope'             => array(
				'he' => 'הנחיות התזונה הישראליות ו-WHO מתארות עקרונות של הלימה, איזון, מתינות ומגוון ברמת דפוס האכילה. סימון תזונתי מספק בסיס להשוואת מנה ול-100 גרם.',
				'en' => 'Israeli dietary guidance and WHO describe adequacy, balance, moderation and diversity at dietary-pattern level. Nutrition labelling provides a basis for comparison per portion and per 100 grams.',
			),
			'limitations'                => array(
				'he' => 'אין גודל מנה יחיד שמתאים לכל אדם. צרכים אישיים תלויים בגיל, בגוף, בפעילות ובמצב בריאות, ולכן העמוד אינו מחשבון קליני.',
				'en' => 'No single portion size suits every person. Individual needs depend on age, body size, activity and health, so the guide is not a clinical calculator.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'מציגים משקל מנה, רכיבים עיקריים ואפשרויות תוספת. אפשר לעזור לקורא לבנות ארוחה מגוונת בלי להבטיח שהיא מתאימה לצרכיו הרפואיים.',
				'en' => 'Show the portion weight, principal components and available sides. Readers can be helped to assemble a varied meal without a promise that it suits their medical needs.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'editorial_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => false,
				'israeli_food_law_review'  => false,
				'privacy_review'           => false,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => true,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-shakshuka',
				'dish-couscous-beef',
				'dish-beef-meatballs-gravy',
			),
			'related_ingredient_codes'   => array(
				'ingredient-vegetables',
				'ingredient-legumes',
				'ingredient-grains',
				'ingredient-protein-foods',
			),
			'source_ids'                 => array(
				'israel-dietary-guidelines',
				'who-healthy-diet',
				'israel-nutrition-labeling-regulations',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-group-meal-planning',
			'slug'                       => array(
				'he' => 'planning-meals-for-groups',
				'en' => 'en-planning-meals-for-groups',
			),
			'title'                      => array(
				'he' => 'תכנון ארוחות לקבוצה, משרד או ארגון',
				'en' => 'Planning meals for a group, workplace or organization',
			),
			'short_answer'               => array(
				'he' => 'תכנון קבוצתי מתחיל במספר הסועדים, דפוס השירות, העדפות מזון ובקשות בטיחות. עדיף לאסוף כמויות מצרפיות, למשל מספר מנות טבעוניות, ולהעביר אלרגיות מזוהות למסלול מוגן ואנושי.',
				'en' => 'Group planning starts with headcount, service pattern, food preferences and safety requests. Aggregate counts, such as the number of vegan meals, are preferable, while identified allergy information belongs in a protected human-reviewed process.',
			),
			'evidence_scope'             => array(
				'he' => 'מקורות התזונה תומכים בגיוון ובהרכב כולל של ארוחות. מקורות בטיחות ופרטיות תומכים בהפרדה בין העדפה, אלרגיה ומידע בריאותי מזוהה.',
				'en' => 'Nutrition sources support variety and overall meal composition. Food-safety and privacy sources support separating preferences, allergies and identifiable health information.',
			),
			'limitations'                => array(
				'he' => 'מספר מנות לפי קטגוריה אינו תחליף לבדיקת אלרגיה פרטנית או תכנון קליני. אין לאסוף אבחנה רפואית כאשר אפשר לספק את השירות בלי מידע זה.',
				'en' => 'A count of meals by category does not replace individual allergy review or clinical planning. A diagnosis should not be collected when the service can be provided without it.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'טופס הצעה אוסף מספר מנות לפי העדפה ומסמן בנפרד בקשות שמחייבות שיחה. המטבח מקבל ספירה מאושרת, גרסאות תפריט והוראות בטיחות, בלי לחשוף מידע אישי שאינו נחוץ.',
				'en' => 'A proposal form collects meal counts by preference and separately flags requests requiring a conversation. The kitchen receives approved counts, menu versions and safety instructions without unnecessary personal information.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'institutional_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => true,
				'israeli_food_law_review'  => true,
				'privacy_review'           => true,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => false,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-shakshuka',
				'dish-couscous-beef',
				'dish-yemenite-beef-soup',
			),
			'related_ingredient_codes'   => array(
				'ingredient-vegetables',
				'ingredient-legumes',
				'ingredient-grains',
				'ingredient-protein-foods',
			),
			'source_ids'                 => array(
				'israel-dietary-guidelines',
				'who-healthy-diet',
				'codex-allergen-management',
				'israel-privacy-amendment-13',
			),
			'last_reviewed'              => '2026-07-31',
		),
		array(
			'id'                         => 'nutrition-guide-preference-allergy-medical',
			'slug'                       => array(
				'he' => 'preference-allergy-and-medical-needs',
				'en' => 'en-preference-allergy-and-medical-needs',
			),
			'title'                      => array(
				'he' => 'העדפת מזון, אלרגיה וצורך רפואי אינם אותו דבר',
				'en' => 'A food preference, an allergy and a medical need are different',
			),
			'short_answer'               => array(
				'he' => 'העדפה מתארת בחירה, אלרגיה היא עניין של בטיחות מזון, וצורך רפואי דורש שיקול מקצועי אישי. האתר יכול להסביר ולסנן מידע עובדתי, אך אינו מאבחן ואינו מחליף תזונאי או רופא.',
				'en' => 'A preference describes a choice, an allergy is a food-safety matter, and a medical need requires individual professional judgement. A website can explain and filter factual information, but it does not diagnose or replace a dietitian or physician.',
			),
			'evidence_scope'             => array(
				'he' => 'מקורות בטיחות מזון מגדירים ניהול אלרגנים, מקורות פרטיות מסווגים מידע בריאותי כמידע רגיש, ומשרד הבריאות מסדיר מי רשאי להציג עצמו כתזונאי דיאטן.',
				'en' => 'Food-safety sources cover allergen management, privacy guidance classifies health information as sensitive, and the Ministry of Health regulates who may present themselves as a dietitian in Israel.',
			),
			'limitations'                => array(
				'he' => 'העמוד אינו מפרש תסמינים, אינו בוחר תפריט למחלה ואינו קובע התאמה לילד, להריון, לצליאק, לסוכרת, למחלת כליה או למצב רפואי אחר.',
				'en' => 'The guide does not interpret symptoms, select a disease-specific menu or decide suitability for a child, pregnancy, celiac disease, diabetes, kidney disease or another medical condition.',
			),
			'practical_culinary_meaning' => array(
				'he' => 'מסננים ציבוריים נשארים ברמת רכיבים, העדפות וערכים מאומתים. אלרגיה מפעילה בדיקה אנושית, וצורך רפואי מופנה לבעל מקצוע מורשה במקום לקבל תשובה אוטומטית.',
				'en' => 'Public filters remain limited to ingredients, preferences and verified values. An allergy triggers human review, while a medical need is referred to a licensed professional rather than answered automatically.',
			),
			'public_status'              => 'draft',
			'internal_status'            => 'governance_reference',
			'index_policy'               => 'noindex',
			'publish_by_default'         => false,
			'review_requirements'        => array(
				'licensed_dietitian'       => true,
				'food_safety_reviewer'     => true,
				'israeli_food_law_review'  => true,
				'privacy_review'           => true,
				'culinary_editor'          => true,
				'hebrew_editor'            => true,
				'english_editor'           => true,
				'source_link_check'        => true,
				'recipe_method_validation' => false,
			),
			'related_dish_codes'         => array(
				'dish-sabich-plate',
				'dish-shakshuka',
				'dish-kubeh-beet-soup',
				'dish-couscous-beef',
			),
			'related_ingredient_codes'   => array(
				'ingredient-egg',
				'ingredient-sesame-tahini',
				'ingredient-wheat',
				'ingredient-fish',
				'ingredient-milk',
			),
			'source_ids'                 => array(
				'codex-allergen-management',
				'israel-privacy-amendment-13',
				'israel-dietitian-licensing',
				'acg-celiac-guideline',
			),
			'last_reviewed'              => '2026-07-31',
		),
	),
);
