# Family Control Panel
Vorab: das Plugin wurde vor einiger Zeit im Auftrag und in Zusammenarbeit mit @berrie entwickelt - sie hat damals die detailreichen Vorgaben / Ideen / Funktionen bestimmt und das Plugin seit Jahren in ihren Foren im Einsatz. <b>Danke</b>, dass ich das Plugin veröffentlichen darf. ♥

<hr />

Dieses Plugin erweitert euer MyBB-RPG-Forum um die Möglichkeit, auf einer Extraseite die User die Familien ihrer Charaktere erstellen zu lassen und nach Generationen aufgeteilt in ihrem Profil anzeigen zu lassen. 

Die neue Seite wird über euerforum.de/family.php erreicht. 

<ul>
<li> Anlage von Familien mit allgemeinem Infos 
<li> Anlage einzelner Familienmitglieder, aufgeteilt nach Generationen
<li> Darstellung der Familie mit allen Infos im Profil des Charakters
<li> Automatische Erstellung eines Gesuchsthreads zur Familie im Gesuchsunterforum
<li> Filterung der Familien nach Informationen wie z.B. Gesellschaftsschicht
<li> Filterung von bespielbaren Familienmitgliedern nach Alter & Geschlecht
<li> Anzeige von bespielbaren oder nicht bespielbaren Mitgliedern
<li> Vorhandene Familienmitglieder können sich per Klick in die Familie eintragen
<li> Familienmitglieder können per Klick reserviert werden
<li> MyAlerts-Benachrichtigung bei Reservierung eines Familienmitglieds
<li> Darstellung von vorhandenen Familienmitgliedern mit Avatar & Profillink
<li> Sollte zu einem Mitglied ein Einzelgesuch vorhanden sein, kann dieses verlinkt werden
<li> Familien-"Besitzer" können Reservierungen wieder freigeben
<li> Eigene Reservierungen werden auf dem Index angezeigt
<li> Angabe eines Default-Bilds für Familienmitglieder im Admin CP
<li> Angabe eines Bilds für Familienmitglieder durch Ersteller

</ul>

<h1>Voraussetzungen</h1>
Jeder Spielername darf nur <b>einmal</b> vergeben sein, da das Reservierungssystem damit verbunden ist.

<h1>Plugin funktionsfähig machen</h1>
<ul>
<li>Die Plugin-Datei ladet ihr in den angegebenen Ordner <b>inc/plugins</b> hoch.
<li>Die Language-Dateien ladet ihr in den entsprechenden Sprachordner.
<li>Das Plugin muss nun im Admin CP unter <b>Konfiguration - Plugins</b> installiert und aktiviert werden
<li>In den Foreneinstellungen findet ihr nun - ganz unten - Einstellungen zu "Familien Control Panel". Macht dort eure gewünschten Einstellungen.
</ul><br />

Das Plugin ist nun einsatzbereit. Solltet ihr schon einiges an eurem Forum gemacht haben, und nicht wie ich im Testdurchlauf ein Default-Theme verwenden, kann es sein, dass nicht alle Variablen eingefügt werden. Sollte euch eine Anzeige fehlen, könnt ihr auf folgende Variablen zurückgreifen:

<blockquote>{$index_family}  // Link zur Reservierungsübersicht (index)<br />
ruft index_family auf<br /><br />

{$member_profile_family} // Familie im Profil (member_profile)<br />
ruft member_profile_family auf<br />

{$showthread_family} // Familie überm Post im Gesuchsbereich (showthread)<br />
ruft showthread_family auf
</blockquote>

<h1>Template-Änderungen</h1>
Folgende Templates werden durch dieses Plugin <i>neu hinzugefügt</i>:

<ul>
<li>family
<li>family_addfamily 	
<li>family_addmember 	
<li>family_claim 	
<li>family_claimed 	
<li>family_claimed_take 	
<li>family_claim_guest 	
<li>family_claim_unplayable 	
<li>family_editfamily 	
<li>family_editmember 	
<li>family_filter_families 	
<li>family_filter_families_bit 	
<li>family_filter_members 	
<li>family_filter_members_bit 	
<li>family_navigation 	
<li>family_navigation_member 	
<li>family_view 	
<li>family_view_generations 	
<li>family_view_generations_member
<li>index_family 
<li>index_family_bit
<li>member_profile_family
<li>showthread_family
</ul>

Folgende Templates werden durch dieses Plugin <i>bearbeitet</i>:
<ul>
<li>index
<li>member_profile
<li>showthread
</ul>

<h1>Empfohlene Plugins</h1>
<a href="https://github.com/MyBBStuff/MyAlerts" target="_blank">MyAlerts</a> von euanT<br />

<h1>Demo</h1>

Die Screenshots stammen aus dem <a href="https://whokilledthecat.de/">[ curiosity ] killed the cat</a> von der lieben berrie ♥

<center><img src="https://snipboard.io/rhsUZk.jpg" />

<img src="https://snipboard.io/KJHzG2.jpg" />

<img src="https://snipboard.io/7blesY.jpg" />

<img src="https://snipboard.io/QjidOg.jpg" />
</center>
