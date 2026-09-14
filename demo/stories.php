<?php
/**
 * Original demo serials bundled with the plugin.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inkbound_p = static function ( ...$paras ): string {
	$html = '';
	foreach ( $paras as $para ) {
		$html .= '<p>' . $para . '</p>';
	}
	return $html;
};

return array(
	array(
		'title'     => 'The Gilded Deep',
		'slug'      => 'the-gilded-deep',
		'subtitle'  => 'Salvage in a city that drowned itself on purpose.',
		'excerpt'   => 'The libraries of Vellum were flooded to keep rivals from reading them. Pell dives for what still has a spine.',
		'synopsis'  => $inkbound_p(
			'Vellum paid to sink its own archives. Better a drowned book, the merchants said, than a competitor with a clean copy. A generation later the harbor is a stacked necropolis of reading rooms, and salvage divers work the stacks by lantern and lung.',
			'Pell takes contracts no one else wants: catalogs that still have names attached, doors that should not hold air. When a dive finds a librarian who never left, the city above starts asking who paid for the flood — and who is still paying to keep it wet.'
		),
		'age'       => 'teen',
		'warnings'  => 'drowning, grief, class violence',
		'schedule'  => 'Sundays',
		'status'    => 'ongoing',
		'genres'    => array('Fantasy', 'Adventure' ),
		'tropes'    => array( 'found family', 'drowned city', 'reluctant hero' ),
		'featured'  => true,
		'date'      => '2026-07-06 09:00:00',
		'cover'     => 'gilded-deep.png',
		'chapters'  => array(
			array(
				'number' => '1',
				'slug'   => 'the-wake-price',
				'title'  => 'The Wake Price',
				'note'   => 'This serial updates on Sundays. Chapter 2 is already up.',
				'content' => $inkbound_p(
					'Pell sold her last dry hour to the harbor office and still came up short. The clerk did not look at her when he said it. He looked at the slate where wake-prices were chalked in a hand too neat for the work: so much per lung, so much per lantern, so much extra if you brought a book up intact. Intact was a joke. Paper that had lived in the Gilded Deep came up as felt.',
					'“Contract twenty-seven,” the clerk said. “Lower stacks, west colonnade. Catalog only. No retrieval.”',
					'Catalog-only meant they wanted names, not pages. Names could still be sold. Pages were a liability. Pell signed with a thumbprint because her letters always came out as salvage marks, and because the clerk already knew she would take it. Nobody else dove west. The colonnade had eaten two lamps last month and returned one boot.',
					'On the quay the city smelled like wet coin. Vellum had flooded its libraries in a single night, the story went, after a trade war turned into a reading war. Better a drowned ledger than a rival with a clean copy. A generation later the harbor was a stacked necropolis of reading rooms, and the people who could still afford dry paper hired people like Pell to bring back whatever still had a spine.',
					'She checked her hose twice. The lantern was a cheap sunstone, already clouding. Her partner for the dive was a boy named Crick who talked too much on the way down and not at all when it counted. He flashed three fingers: west, then down, then the old signal for <em>do not open anything that answers</em>.',
					'The first reading room was a ballroom of shelves. Fish moved through the aisles like overdue patrons. Pell tied off on a column whose gilt had gone green and started the catalog the way she had been taught — not by title, which the water had eaten, but by the brass plates still screwed to the ends of the stacks. House names. Debt names. The kind of names that still paid wake-prices.',
					'Crick tugged her line. At the far end of the colonnade a door stood closed, which was not the strange part. The strange part was the seam of it, which brightened and dimmed as if something on the other side was breathing.',
					'Pell wrote the plate names on her slate until her fingers cramped. She did not touch the door. Catalog only. She was already short on dry hours. She was not paid to find out who had stayed.'
				),
			),
			array(
				'number' => '2',
				'slug'   => 'catalog-of-sunk-names',
				'title'  => 'Catalog of Sunk Names',
				'content' => $inkbound_p(
					'The harbor office paid her in scrip that could be exchanged for bread, lamp oil, or a single night in a dry bunk if she did not mind the bunk belonging to whoever had drowned last. Pell took the oil. Crick took the bunk and was gone by morning, which she chose to read as luck.',
					'Contract twenty-seven had a footnote she had missed: <em>Any unlisted door is to be reported, not opened.</em> She reported it. The clerk’s neat hand paused. He asked her to describe the breathing. She said it was like a bellows with manners. He wrote that down too, then paid her the catalog rate and not the door rate, because a door was not a book.',
					'By afternoon a woman in a rust-colored coat was waiting on the quay with Pell’s slate copied in a better script. “You dove west,” she said. Not a question. “I need the names you did not sell them.”',
					'Pell had sold all the names. That was the job. The woman — she gave no name, only a house mark on a ring, gilt gone the same green as the columns — offered three dry days and a better lantern if Pell went back for the plates that had been pried off before the flood.',
					'“Those plates are in the silt,” Pell said.',
					'“Then you are a silt worker today.”',
					'They went at slack tide. The rust coat did not dive. She sat in a hired skiff and read a paper book with a spine so dry it made Pell’s mouth hurt. Crick, reappearing as if the bunk had ejected him, took the second line. The sunstone this time was clean. It made the colonnade look like a church that had admitted the sea on purpose.',
					'The pried plates were where the woman had said, under a drift of gilt flakes. Pell brushed silt until letters came up: not house names. Staff names. Librarians. The people Vellum had paid to stay with the books when the pumps were reversed.',
					'One plate was newer than the flood. The brass still had edges. It read: <em>MARELL KEEP / STILL ON DUTY</em>.',
					'Crick pointed at the breathing door. Pell shook her head. Catalog only, even when the catalog had started writing itself. They surfaced with the plates in a mesh bag and the rust coat did not offer the dry days. She offered a fourth name, spoken, not written, and told Pell that if the door opened from the inside, the wake-price would not be enough to bury what came out.'
				),
			),
			array(
				'number' => '3',
				'slug'   => 'air-on-the-other-side',
				'title'  => 'Air on the Other Side',
				'content' => $inkbound_p(
					'Pell went back alone because Crick had decided he liked bunks more than brass, and because the rust coat’s fourth name had been her mother’s. Not the name on any plate. The name the harbor used when they still had a kitchen above a bindery, before the flood made kitchens a rumor.',
					'The door was still breathing. Up close the seam smelled like paper that had never been wet. Pell put her palm on the wood and felt a knock answer from the other side, polite, three times, the way you knocked on a reading-room door when you did not want to startle a copyist.',
					'She should have surfaced. She had a lantern, a hose, and a contract that paid her not to be curious. She knocked back.',
					'The door opened inward, which was wrong for a drowned building, and a pocket of air took her like a swallowed word. She came up on her hands in a room that should not have existed: shelves dry as bone, lamps lit with a light that was not sunstone, a desk with a ledger open to today’s date. A woman in librarian gray sat behind it with ink on her mouth, as if she had been eating the work.',
					'“You are late for returns,” the woman said. Her voice had the careful dryness of someone who had been speaking only to herself. “I am Marell Keep. I stayed. The catalog is behind. The pumps were not supposed to be a policy. They were supposed to be a night.”',
					'Pell found her own voice, salt-raw. “The city paid to drown you.”',
					'“The city paid to drown the books,” Marell said. “I was included in the inventory. That is not the same as being paid.” She turned the ledger. Staff names, then house names, then a column titled <em>Who ordered the valves</em>. The last cell was blank, waiting. “You brought plates. Good. I can finish the dead. I cannot finish the living from here.”',
					'Pell looked at the door, still open on black water. Her hose floated in it like a thought she had not finished. “If I leave, can I come back?”',
					'Marell smiled with the ink still on her teeth. “That depends on whether you tell the harbor office I am a door, or a person. Doors get reported. Persons get retrieved. Retrieval, in Vellum, is not always a rescue.”'
				),
			),
			array(
				'number' => '4',
				'slug'   => 'the-librarian-who-stayed',
				'title'  => 'The Librarian Who Stayed',
				'note'   => 'Next week: the rust coat comes back with a writ.',
				'content' => $inkbound_p(
					'Pell reported a door. She did not report a person. The clerk paid the door rate this time, which was less than a life and more than a catalog, and asked if the breathing had stopped. Pell said it had learned manners. He did not write that down.',
					'The rust coat found her anyway, in the oil line, and did not bother with the skiff. “You opened it,” she said. The house mark on her ring had been polished. Gilt was back in fashion when you needed to look like you had never been poor. “Marell Keep is not a relic. She is a witness. Witnesses are expensive.”',
					'“Then pay her,” Pell said.',
					'“We did. We paid her to stay. That contract does not expire because the water got ideas.” The woman — Pell decided to call her Gilt, since she would not give a name — laid a writ on the crate between them. Retrieval. Authorized. West colonnade. Live inventory. “You will bring her up. She will sign that the flood was a weather event. Then the names you sold become a closed account.”',
					'Pell thought of the ledger’s blank cell. Who ordered the valves. She thought of her mother’s name spoken like a password. She thought of Crick, who had the right idea about bunks.',
					'“I’ll dive,” she said. “I won’t retrieve a person who is still on duty.”',
					'Gilt’s mouth did something that might have been a smile on a kinder face. “Then you are in the inventory too. The Deep does not distinguish. That was the point.”',
					'That night Pell sat on the quay with a dry page she had stolen from no one, because it had come from Marell’s desk and Marell had pressed it into her glove: a list of valve-men, pump-houses, and a single merchant seal she recognized from the wake-price slate. The harbor office was not a neutral clerk. It was a front for the houses that had paid to drown their own reading, and were still collecting rent on the silence.',
					'She would go back. Not for Gilt’s writ. For the blank cell, and for the librarian who had stayed, and because the Gilded Deep had started cataloging the living, and Pell’s name was already in the silt.'
				),
			),
		),
	),
	array(
		'title'     => 'Signal Hollow',
		'slug'      => 'signal-hollow',
		'subtitle'  => 'A colony radio that knows the weather before the sky does.',
		'excerpt'   => 'Kei runs night radio on a world that is officially winding down. A voice on a dead frequency says her name like a check-in.',
		'synopsis'  => $inkbound_p(
			'Helix Station is in orderly withdrawal: fewer ships, fewer shifts, more forms that say the soil won. Kei prefers the night board. The official channels are quiet. The unofficial ones are supposed to be dead.',
					'Then a voice comes through on a hollow frequency — no callsign, no packet header — and reads tomorrow’s weather before the sensors post it. It knows Kei’s name. It knows who is still listening. The withdrawal, it turns out, is not as orderly as the memos.'
		),
		'age'       => 'teen',
		'warnings'  => 'isolation, institutional neglect',
		'schedule'  => 'Wednesdays',
		'status'    => 'ongoing',
		'genres'    => array( 'Science Fiction', 'Mystery' ),
		'tropes'    => array( 'found signal', 'dying colony', 'night shift' ),
		'featured'  => true,
		'date'      => '2026-07-15 21:00:00',
		'cover'     => 'signal-hollow.png',
		'chapters'  => array(
			array(
				'number' => '1',
				'slug'   => 'dead-air-named',
				'title'  => 'Dead Air, Named',
				'content' => $inkbound_p(
					'Kei liked the night board because it did not require optimism. Helix Station’s day shift still performed the fiction of a colony: greenhouses counted, children counted, ships counted. Night shift counted static. Static did not lie about leaving.',
					'The withdrawal memos used words like <em>orderly</em> and <em>soil fatigue</em>. What they meant was: the beans had failed twice, the third ice hauler had rerouted, and nobody important wanted to be the last signature on a world. Kei’s job was to keep the emergency channel open in case anyone important changed their mind.',
					'At 02:14 the hollow frequency — a band operations had decommissioned because it bounced wrong off the magnetite hills — said her name. Not a packet. Not a callsign. Just “Kei,” the way a tired supervisor said it when they needed a form signed, and then a weather report for a morning that had not happened: wind from the east basin, grit to two meters, greenhouse three to be tarped by 09:00.',
					'Kei logged it as a dream because the alternative was paperwork. At 08:52 greenhouse three lost a panel in east-basin wind. Grit to the mark on the wall they used instead of meters. She did not tell day shift. Day shift believed in sensors.',
					'The second night the voice said, “You logged me as a dream. I am still here. Everyone still listening is still here. That is not the same as being counted.”',
					'Kei opened a private reel. The station’s archive was supposed to be write-once, but night radio had always had a drawer that did not report to memos. She labeled the reel HOLLOW and did not tell the board she had started answering.'
				),
			),
			array(
				'number' => '2',
				'slug'   => 'weather-that-hasnt-happened',
				'title'  => 'Weather That Hasn\'t Happened',
				'content' => $inkbound_p(
					'The voice would not give a name. It gave forecasts. It gave them early, and it gave them with the petty accuracy of someone who had worked the same greenhouses: which gasket would go, which kid would try to watch the grit from the west catwalk, which supervisor would call it a sensor drift.',
					'Kei started tarping greenhouse three at 08:40 on the days she was told to. Day shift called her superstitious and then borrowed her tarps. The withdrawal calendar on the mess wall lost another week. A ship that had been “evaluating capacity” evaluated itself back to the inner worlds.',
					'“Who are you,” Kei said into the hollow band, because the reel was running and because she was tired of being the only person in the room who believed the air.',
					'“I am the part of the station that was not invited to leave,” the voice said. “You keep the emergency channel open for people who already have tickets. I keep a channel open for people who do not.”',
					'Kei checked the personnel list. Withdrawal was voluntary until it was not. There were still 411 names. There were 380 bunks assigned. The math had been wrong for months and the memos called it rounding.',
					'She walked the unassigned corridors with a hand lamp. The magnetite hills made compasses rude. In a storage ring that the map called empty she found a door with a radio seal, the old kind, and a note in grease pencil: <em>If you can hear us, you are already on this side.</em>',
					'The door was locked from her side. The voice, when she returned to the board, sounded faintly amused. “You found the hollow. Do not open it on day shift. They will call it a soil problem and pour concrete. Concrete does not improve the weather.”'
				),
			),
			array(
				'number' => '3',
				'slug'   => 'the-hollow-frequency',
				'title'  => 'The Hollow Frequency',
				'content' => $inkbound_p(
					'Operations noticed the tarps. Operations noticed the reel. Operations did not notice the thirty-one missing bunks, because missing bunks were a day-shift problem and day shift had a ship to miss.',
					'Supervisor Adel came to the night board with a smile that had been trained on families. “You’re using a dead band,” she said. “That’s a citation if I log it. I would rather log a commendation for greenhouse three. Tell me you are not talking to withdrawal folklore.”',
					'Kei almost told the truth. Then she thought of concrete. “I am talking to weather,” she said. “The sensors lag. The hills bounce.”',
					'Adel’s smile held. “The hills bounce what we put into them. If something is putting weather back, I need a name for the report. Folklore does not get seats.”',
					'After Adel left, the hollow band was quiet long enough for Kei to hate the quiet. Then: “She is not cruel. She is leaving. Cruelty and leaving share a calendar here. If you want the thirty-one counted, you will have to put them on a frequency operations still respects.”',
					'Kei patched the hollow band, just for a minute, onto the emergency channel — the one she was paid to keep open for people with tickets. She did it at 03:01, when even Adel slept. A chorus of breaths filled the official air. Not words. Presence. The kind of proof a sensor could not round.',
					'In the morning the emergency log showed a spike and a note auto-generated by a system that still thought it was useful: <em>UNIDENTIFIED OCCUPANCY / HOLLOW RING / AWAITING INSTRUCTION</em>.',
					'Kei filed the instruction herself, as night radio, in a format memos could not ignore: a passenger manifest with thirty-one names she had pulled off old work rotas, and a weather report for a day that had not happened yet, in which a ship would have to decide whether orderly withdrawal included the people in the walls.'
				),
			),
			array(
				'number' => '4',
				'slug'   => 'everyone-still-listening',
				'title'  => 'Everyone Still Listening',
				'note'   => 'The ship’s reply arrives next Wednesday.',
				'content' => $inkbound_p(
					'The ship did not like the manifest. Ships liked clean numbers. Adel did not like that the emergency channel had grown a population overnight. She liked even less that the names were real: techs from the first decade, a botanist listed as transferred, a child who had been “soil-fatigued” off the school roll and not onto any departure.',
					'“You will close the hollow band,” Adel said. It was not a citation. It was a plea wearing a uniform. “If I put thirty-one extra bodies on a withdrawal that already failed the beans, I do not get a ship. I get a hearing.”',
					'Kei thought of seats. She thought of concrete. She thought of a voice that had been doing the work of a government without being invited to meetings. “Then the hearing should meet the weather,” she said. “They already know the grit better than your sensors.”',
					'That night she opened the storage-ring door. Not on day shift. The hollow was not a ghost story. It was a room the magnetite had made kind: bunks, a still, a radio older than the station’s pride. People who had been rounded off looked up from a card game as if she were a forecast.',
					'“You’re Kei,” said a man with greenhouse dirt in the lines of his hands. “You tarp three early. Thank you. We would like to be on the next count as ourselves, not as occupancy.”',
					'She put them on the official channel properly this time, names and all, and let the hollow frequency stay open beside it like a second lung. When the east-basin wind arrived on schedule, greenhouse three held. The ship, still in orbit, requested clarification. Clarification, Kei was learning, was what leaving called it when the people who stayed filed paperwork.',
					'The voice — she could see the old radio it belonged to now, a handset passed between night watches — said, quietly, “Everyone still listening is still here. Log that as fact. Facts are harder to unseat than folklore.”',
					'Kei logged it. Then she requested thirty-one additional tarps, and a seat at the withdrawal meeting, and a name for the band that was no longer dead.'
				),
			),
		),
	),
	array(
		'title'     => 'Salt & Cipher',
		'slug'      => 'salt-cipher',
		'subtitle'  => 'A parish ledger that records drownings before the tide.',
		'excerpt'   => 'Whitby, 1891. A clerk inherits a book that already knows who the sea will take.',
		'synopsis'  => $inkbound_p(
			'When parish clerk Ned Harrow dies, his nephew inherits a ledger that does not match the burial register. The extra names are dated in the future. The cipher in the margins is the tide table.',
			'Elise Quinn — who keeps the actual accounts, and therefore the actual dead — has to decide whether to warn a town that will call her hysterical, or let the ledger finish its arithmetic. Completed in four chapters.'
		),
		'age'       => 'teen',
		'warnings'  => 'drowning, mourning, Victorian institutional cruelty',
		'schedule'  => 'Complete',
		'status'    => 'completed',
		'genres'    => array( 'Mystery', 'Historical' ),
		'tropes'    => array( 'found manuscript', 'small town', 'codebreaking' ),
		'featured'  => false,
		'date'      => '2026-05-01 10:00:00',
		'cover'     => 'salt-cipher.png',
		'chapters'  => array(
			array(
				'number' => '1',
				'slug'   => 'the-clerks-remainder',
				'title'  => 'The Clerk\'s Remainder',
				'label'  => 'Chapter 1',
				'content' => $inkbound_p(
					'Ned Harrow died the way parish clerks often die: between one column and the next, with ink on his thumb and a look of having been interrupted by something as rude as mortality. Elise Quinn, who had kept the burial register while Ned kept the stories people told about the dead, found the second ledger in the desk that smelled of salt and boiled sweets.',
					'It did not match. The official register had Sunday’s drowning — a boy from the jetty, already buried, already paid for. Ned’s ledger had the boy, and then three names Elise did not know, dated Thursday week, Friday, and a morning so specific it included the hour the tide would turn.',
					'In the margin, in Ned’s cramped hand: <em>Not prophecy. Arithmetic. See Whitby table, 1889 reprint, page 12, inverted.</em>',
					'Elise was not a hysteric. She was a woman who could add. She inverted page 12 of the tide table the way you invert a cipher when you have been married to a clerk’s habits if not to the clerk. High water became a key. The names were not invented. They were last week’s visitors to the parish poor-book, copied forward by an interval that matched the spring tide.',
					'She went to the vicar. The vicar suggested grief, rest, and the desirability of not alarming fishermen. Elise went to the jetty instead, counted the men who could not swim, and found one of Thursday’s names mending a net with his mouth full of twine. She did not know how to tell a living man he was a remainder.'
				),
			),
			array(
				'number' => '2',
				'slug'   => 'names-in-the-margin',
				'title'  => 'Names in the Margin',
				'content' => $inkbound_p(
					'The mending man was called Bram Sile. He laughed when Elise asked if he would stay off the water Thursday. He had a child’s coat to pay for and a belief that the sea took who it liked, which was true enough to be useless. She gave him Ned’s date anyway. He said clerks saw drowning in every column.',
					'Elise copied the three future names into a book of her own, because she did not trust a dead man’s arithmetic to survive a vicar’s fireplace. She walked the lanes and matched them to faces: Bram; a visiting school inspector with wet shoes; a girl from the smokehouse who sang while she packed herring.',
					'The inspector was easiest. He believed in reports. Elise wrote him a report: jetty unsound, tide vicious, Thursday unsuitable for measurements. He postponed. The ledger, when she checked it that evening, had struck his name through with a line so neat it made her ill. Arithmetic could be bargained with. That was worse than prophecy. Prophecy did not take correction.',
					'Bram did not postpone. The girl from the smokehouse — Tamsin — listened, which was not the same as staying ashore. She asked who had written her into a drowning, as if the handwriting were the offense. Elise showed her the tide table. Tamsin, who packed herring by the moon whether the church liked it or not, said, “Then we move the moon.”',
					'They could not move the moon. They could move a jetty ladder. They could lie about a catch. They could hide a man in a choir loft, which smelled of damp wool and was, Elise realized, also a kind of column: names stacked above the parish, unpaid.'
				),
			),
			array(
				'number' => '3',
				'slug'   => 'a-tide-that-doesnt-turn',
				'title'  => 'A Tide That Doesn\'t Turn',
				'content' => $inkbound_p(
					'Thursday came in sideways. The spring tide ignored the vicar and the inspector’s postponed report. Bram went to the water because hiding in a loft felt like dying early. Elise went after him with a boat hook and no authority. Tamsin brought rope and a voice that could cut herring and weather alike.',
					'They did not save him so much as relocate the drowning. The jetty ladder, moved in the night, dumped him into a stretch of harbor that had a ladder still attached. He came up swearing, alive, expensive. The ledger’s neat hand — and it was still adding lines, which meant either Ned had trained a ghost or someone living was keeping the book — wrote <em>remainder carried</em> beside Bram and added a new date, later, as if the sea kept accounts receivable.',
					'Elise sat in the clerk’s office with wet hems and understood the cruelty of it. The cipher was not a warning system. It was a bookkeeping of a town that had decided some people were already lost, and was only arguing with the calendar. Someone was feeding the ledger: not the tide table alone, but the poor-book, the vicar’s visiting list, the smokehouse wages. A person with access to all three.',
					'She locked the ledger in her own trunk and went to see who kept keys to the parish chest besides a dead clerk. The sexton. The vicar. And Ned’s sister, who had always hated the sea and loved a tidy column, and who had brought Elise the boiled sweets that still scented the desk.'
				),
			),
			array(
				'number' => '4',
				'slug'   => 'what-the-ledger-owed',
				'title'  => 'What the Ledger Owed',
				'note'   => 'This story is complete.',
				'content' => $inkbound_p(
					'Agnes Harrow did not deny it. Denial was messy. She sat in the kitchen that still thought it belonged to Ned and said, “He would not stop writing drownings after they happened. I put them where they could be useful. A parish that knows the bill can sometimes pay it early.”',
					'“You copied the poor into the future,” Elise said. “That is not payment. That is a collection notice.”',
					'Agnes’s mouth was Ned’s mouth, minus the ink. “The inspector lived. Bram lived. Tamsin will live if she stays off the smokehouse roof in the next gale, which I also wrote, because I am not a monster. I am a remainder. The town uses women like us to keep the columns honest and then calls us hysterical when the arithmetic shows.”',
					'Elise thought of the vicar’s fireplace, of ladders moved in the night, of a ledger that had learned to strike through and postpone. She did not burn the book. Burning was how men ended stories they did not want to keep feeding. She took it to Tamsin and Bram and the inspector, and they made a fourth register: not of the dead, and not of the doomed, but of who had been warned and what they had done with the warning.',
					'The tide still turned. The sea still took who it liked. But on Friday the girl from the smokehouse was in the loft by choice, counting names instead of herring, and the ledger — Agnes’s hand, Ned’s habit, Elise’s stubborn addition — had a new column titled <em>refused</em>.',
					'It was not salvation. It was better bookkeeping. In Whitby, in 1891, that was the kind of miracle a clerk could afford.'
				),
			),
		),
	),
);
