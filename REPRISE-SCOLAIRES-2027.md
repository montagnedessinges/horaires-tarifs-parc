# Reprise — campagne scolaires 2027 / bons découverte

**Point de situation daté du 16 octobre 2026.** Document de transmission destiné à une nouvelle conversation ChatGPT, sans données personnelles ni liens privés vers les classeurs.

## État vérifié dans GitHub
- Dépôt canonique : `montagnedessinges/horaires-tarifs-parc`. Respecter `AGENTS.md` et `CHATGPT-CONTEXT.md`.
- Version inscrite dans `horaires-tarifs-parc.php` sur `main` lors du contrôle : **1.20.7** (ne pas confondre version du code avec release publiée ou installation sur chaque site).
- Le module **Bons découverte** existe déjà : `includes/class-parcs-ht-discovery-voucher.php`. Ne pas le développer une seconde fois.
- Shortcode public : `[parc_bon_decouverte]` (alias `_fr`, `_en`, `_de`, mais contenu de l'offre volontairement **uniquement en français** dans cette version).
- Module : activé par réglage propre à chaque WordPress, départements autorisés, date de fin facultative, shortcode Contact Form 7 associé, vérification postale Ajax et côté serveur lors de l'envoi du CF7. Aucun bon n'est généré ni envoyé automatiquement : **contrôle et attribution manuels**.
- Une notification Gmail de demande de bon découverte provenant du site Montagne des Singes a été observée le 16/10/2026. Cela ne prouve ni une validation intégrale de son parcours ni le déploiement sur le site Forêt des Singes.

## Suivi scolaire — Google Sheets existants
- Deux classeurs distincts **« SUIVI SCOLAIRE 2027 — Montagne des Singes »** et **« SUIVI SCOLAIRE 2027 — Forêt des Singes »** ont été créés le 16/10/2026. Rechercher ces noms dans Google Drive et **les réutiliser**, ne pas en créer d'autres.
- Onglets existants : Tableau de bord, Établissements, Historique e-mails, Demandes de bons, Billets - stock, Attributions, Visites et réservations, Imports CSV, Mode d'emploi, Paramètres, Annuaire source. Le classeur Montagne a aussi « Bons découverte - lots ».
- MDS : 5 226 lignes de l'annuaire d'origine, 5 076 e-mails renseignés ; 50 billets individuels / 25 lots de 2 en stock selon le Tableau de bord au 16/10 (aucune attribution enregistrée).
- FDS : 3 671 lignes d'annuaire, 3 618 e-mails renseignés ; aucun stock de billets enregistré au 16/10.
- Le Tableau de bord affichait 0 événement e-mail, 0 demande et 0 fichier CSV importé pour les deux parcs au moment de la vérification. **Ces zéros correspondent au suivi saisi, pas à la réalité de tous les e-mails ni aux demandes reçues.**
- Les CSV d'annuaire source ne sont pas équivalents aux exports des événements Brevo. L'onglet historique exige des preuves et statuts réels ; ne pas inventer un envoi individuel.
- Conserver les fichiers sources dans les dossiers Drive de chaque parc, tels que répertoriés dans « Paramètres ». Ne jamais toucher au classeur opérationnel « Réservation MDS ».

## Campagnes Brevo du 16/10/2026 — preuves Gmail
- Confirmation Brevo « Scolaire 2027 » : campagne **envoyée à 5 649 contacts**.
- Confirmation Brevo « Scolaire 2027 FDS » : campagne **envoyée à 3 561 contacts**.
- Cela ne prouve pas 5 649 / 3 561 délivrances : importer les rapports/export détaillés (délivrés, rebonds, blocages, désinscriptions) avant d'attribuer un statut par contact.
- Une demande individuelle par le formulaire MDS est visible dans Gmail et n'a pas encore été rapprochée du tableau ; vérifier l'établissement et les éventuels bons papier avant envoi.

## Attention géographique
- Fichier d'annuaire MDS : départements 25, 54, 57, 67, 68, 70, 88, 90.
- Fichier d'annuaire FDS : départements 12, 15, 19, 24, 31, 46, 81, 82. Un ciblage évoqué ailleurs comprenait 14, 47, 87 et laissait 31 à examiner ; **ne pas confondre le contenu du CSV avec la zone validée**.
- Contrôler les règles de l'offre et les listes de campagne dans chaque site avant toute modification.

## Priorités de reprise — samedi 17 octobre 2026
1. Auditer **l'existant** du module Bon découverte (réglages WordPress sur les deux sites si intervention explicitement demandée), disponibilité réelle du CF7, code postal, réponse de confirmation et absence d'envoi automatique ; corriger seulement une lacune démontrée.
2. Terminer les pages groupes scolaires : conserver les guides pédagogiques, alléger les répétitions, clarifier bon découverte, devis et conditions ; ne pas ajouter de bloc faisant doublon. Vérifier chaque site séparément.
3. Réconcilier confirmations Gmail, réponses CF7 et exports Brevo avec les **deux classeurs déjà créés**, sans détruire les données sources. Tracer campagnes par parc et contacts par événements réels.
4. Contrôler l'éligibilité, les doublons UAI et les bons papier avant toute attribution ; stock limité MDS à 25 lots enregistrés au 16/10, stock FDS non encore constitué.
5. Analyser les contacts bloqués Brevo par **motif**. Ne pas réactiver en masse désinscrits, plaintes spam ou hard bounces sans correction/base juridique appropriée.
6. Avant une modification de l'extension : lire les règles du dépôt, vérifier `main`, la dernière release, les tests ; toute évolution de code nécessite **nouvelle version publiée** et préservation des réglages des deux sites.
7. Ne pas renvoyer les campagnes du 16/10 par erreur ; vérifier la cible des nouvelles relances et le statut réel de délivrance.

## Prompt court à utiliser dans une nouvelle conversation

> Reprends le projet « Scolaires 2027 — Montagne des Singes & Forêt des Singes ». Commence par lire `AGENTS.md`, `CHATGPT-CONTEXT.md` et ce fichier dans le dépôt GitHub `montagnedessinges/horaires-tarifs-parc`, puis consulte les deux Google Sheets existants « SUIVI SCOLAIRE 2027 » et les messages Gmail Brevo du 16/10/2026. Ne recommence ni les classeurs ni le module Bon découverte existant (code main 1.20.7 au 16/10). Établis les différences entre ce qui est confirmé, à tester et à faire, avec priorité à la vérification du formulaire/CF7 sur les deux parcs, à la finition des pages scolaires et au rapprochement des campagnes Brevo (MDS 5 649 envois, FDS 3 561). Ne touche pas au Google Sheet opérationnel Réservation MDS. N'envoie ni bon ni e-mail et ne débloque aucun contact sans contrôle préalable. Mets à jour le document de reprise après chaque session.

**Mise à jour exigée :** ce point est une photographie au 16/10/2026, non une preuve que les prochaines actions ont été réalisées. Actualiser dates, statuts et liens après les opérations.


## Mise à jour du 17 octobre 2026 — suivi des e-mails et bons
- Les deux classeurs « SUIVI SCOLAIRE 2027 » disposent désormais d'un onglet **« Corrections e-mails »**, dédié aux divergences, aux changements confirmés et à leur future réconciliation Brevo. L'annuaire source n'est pas modifié.
- **FDS :** réponse automatique du 16/10 issue de la campagne scolaire indiquant un ancien contact inactif et communiquant une autre adresse de contact. Ancien et nouveau courriel ainsi que la source Gmail sont consignés dans **Corrections e-mails**, **Établissements** et **Historique e-mails** du classeur FDS. Rapprochement avec UAI confirmé. Les informations nominatives et adresses sont volontairement omises du dépôt GitHub : les consulter dans le Google Sheet restreint. **Brevo n'a pas été modifié.** Ne pas traiter l'adresse de remplacement comme un consentement automatique aux mailings.
- **MDS :** une demande de bon est saisie dans **Établissements**, **Demandes de bons**, **Attributions** et **Historique e-mails**. Le premier lot de deux billets est réservé pour cette demande. Le 17/10, l'utilisateur a confirmé avoir envoyé le bon, mais le brouillon reste présent dans le Gmail connecté et aucun envoi correspondant n'y a été retrouvé : l'état de suivi est **« envoi déclaré par utilisateur, preuve à rapprocher »**. Ne jamais redistribuer ou réexpédier le lot avant vérification. Une divergence d'adresse entre demande CF7 et annuaire est consignée dans **Corrections e-mails**, sans correction Brevo automatique.
- Autres réponses de campagne : un simple message d'absence temporaire ne signifie pas une adresse invalide. Les notifications de refus par Gmail à l'adresse interne du parc ne sont pas des preuves d'invalidité de destinataires de la campagne scolaire.
- **Exports Brevo à obtenir pour poursuivre** : 1) CRM > Contacts > exporter les listes utiles avec identifiants **CONTACT_ID**, **EMAIL**, attributs établissement/UAI disponibles, listes et états d'abonnement ; 2) Marketing > Emails > rapports des campagnes « Scolaire 2027 » (MDS) et « Scolaire 2027 FDS » du 16/10/2026 > **Exporter le CSV par contact** ; 3) données détaillées de non-délivrance / rebonds / blocages et désinscriptions si disponibles. Préserver les CSV d'origine, isoler les corrections, ne pas réinscrire les désinscrits ou plaintes spam. Attendre l'export Brevo avant toute importation de correction.
- Documentation officielle Brevo des exports : https://help.brevo.com/hc/fr/articles/5310065850642-Exporter-vos-contacts-%C3%A0-partir-de-Brevo et https://help.brevo.com/hc/fr/articles/19764406559506-Analyser-et-exporter-le-rapport-de-votre-campagne-email .
