<?php
  /*
    Dictionary of words used for bee anatomy and some common permutations (plurals/adjectival forms).
    Uses minimal regex syntax for easier human management
  */
  $anatomy_nouns = array(
    'head' => array(
      'head','mandible','cheek','ocell(us|i)','eye','face','flagell(um|a)','clype(us|al)'
    ),
    'thorax' => array(
      'thorax','tegula(e|)','leg','coxa(e|)','fem(ur|oral|ora)','tibia(e|l|)','(basi|)tars(us|i|al)','wing','scut(um|al)','scutell(um|ar)','propodeum','pronot(um|al)'
    ),
    'abdomen' => array(
      'abdomen','terg(um|al|a|ite)','stern(um|al|a|ite)','fascia(e|)','pygidium'
    )
  );

  const SECTION_START = '<div class="card mb-3 border-secondary"><div class="card-header bg-secondary text-light"><h2 class="card-title mb-0">';
  const HEAD_END_UL_START = '</h2></div><ul class="list-group list-group-flush">';
  const LI_START = '<li class="list-group-item d-flex ps-0"><a href="#" class="bi-bookmark-heart" onclick="toggleHighlight()" role="button" aria-label="Highlight item"></a><span>';
  const LI_END = '</span></li>';
  const SECTION_END = '</ul></div>';

  // Convert dictionary into proper regex search strings for each region
  foreach ($anatomy_nouns as $region_name => $body_region) {
    ${$region_name.'_regex'} = '/';
    foreach ($body_region as $body_part) {
      // If plural form wasn't specified, assume it's "s"
      if (!str_contains($body_part, '(')) {
        $body_part .= '(s|)';
      }
      $body_part = '(\b'.$body_part.'\b)|';
      ${$region_name.'_regex'} .= $body_part;
    }
    ${$region_name.'_regex'} = rtrim(${$region_name.'_regex'}, '|').'/i';
  }

  // Format the given species description
  if (filter_has_var(INPUT_POST, 'desc')) {
    // TODO: Strip unexpected characters and whitespace
    function sanitize_desc ($desc) {
      // return preg_replace('/[^a-z0-9,;\.\-\(\)]|[\s]{2,}/i', ' ', $desc);
      return $desc;
    }
    $orig_desc = filter_input(INPUT_POST, 'desc', FILTER_CALLBACK, ['options' => 'sanitize_desc']);

    // If there's valid text left after sanitization:
    if ($orig_desc) {
      // Split into list items at semicolons
      $lines = explode('; ', $orig_desc);

      $next_region = 'head';
      foreach ($lines as $key => &$line) {
        // Capitalize the first letter and make it a list item
        $line = ($key === 0 ? SECTION_START.'General'.HEAD_END_UL_START : '')
          .LI_START.($key === 0 ? ucfirst($lines[0]) : ucfirst($line)).LI_END;
        // Subdivide description into head-thorax-abdomen
        /*
          Mitchell's descriptions are formatted in a reliable order (at least at the body segment level),
          so: check whether this li contains anatomy words from the next expected body segment; if so,
          we're done with the current body segment — close the list, add in a header with the next
          segment's name, and start a new list
        */
        if ($next_region && preg_match(${$next_region.'_regex'}, $line)) {
          $line = SECTION_END.SECTION_START.ucfirst($next_region).HEAD_END_UL_START.$line;
          $next_region = match ($next_region) {
            'head' => 'thorax',
            'thorax' => 'abdomen',
            'abdomen' => null // Stop looking for matches after reaching the final segment
          };
        }
      }
      // Recombine into one string for output
      $list = implode($lines).'</ul>';
    }
    // Format given species name
    if(filter_has_var(INPUT_POST, 'sp')) {
      function validate_latin($name) { // TODO
        // must be exactly two words
      }
      function format_latin($name) {
        // Strip any non-alphabetic characters and enforce proper capitalization
        return ucfirst(strtolower(preg_replace('/[^a-zA-Z ]/', '', $name)));
      }
      $species = filter_input(INPUT_POST, 'sp', FILTER_CALLBACK, ['options' => 'format_latin']);
      // validate_latin($species);
    }
  }
 ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bee pretty species reader</title>
    <link rel="stylesheet" href="bootswatch-sandstone.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="bootstrap.bundle.min.js"></script>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container-fluid">
        <div class="row flex-fill">
          <div class="col-0 col-xl pe-0"></div>
          <div class="col-md-8 col-lg-6">
            <div><a class="navbar-brand" href="#">bee pretty</a></div>
            <span class="navbar-text">species reader</span>
          </div>
          <div class="col-md-4 col-xl ps-0"></div>
        </div>
      </div>
    </nav>
    <div class="container-fluid">
      <div class="row justify-content-center">
        <!--
          L-M-R cols shift width to keep the sidebar from getting too squished,
          and shift position to keep it attached to the navbar when stacked:
          xs = R(100%) M(100%)
          sm = R(100%) M(100%)
          md =      M(8) R(4)
          lg =  (1) M(6) R(4) (1)
          xl = L(3) M(6) R(3)
        -->
        <!-- Left column is for spacing (and potentially content later)
        and is only needed at size xl -->
        <div class="col-0 col-xl pe-0"></div>
        <!-- Main column is full width at sizes XS and SM, and appears below
        the dropdown toggle for the about info; then 2/3 width with 1 col of
        left-hand margin at size LG, then 50% width at size LG and above
        -->
        <main class="col-md-8 col-lg-6 order-2 order-md">
          <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" class="accordion mb-3 border border-top-0 border-dark rounded-bottom" id="formAccordion">
            <div class="accordion-item">
              <h1 class="accordion-header" id="formHeader">
                <?php if (isset($species)): ?>
                <button class="accordion-button bg-light collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#formContent" aria-expanded="false" aria-controls="formContent" name="button">
                  <?= "<span><i>$species</i> ({$_POST['sexRadio']})</span>" ?>
                </button>
              <?php else: // not .collapsed, aria-expanded=true, different header text ?>
                <button class="accordion-button bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#formContent" aria-expanded="true" aria-controls="formContent" name="button">
                  Enter species description
                </button>
                <?php endif; ?>
              </h1>
              <fieldset id="formContent" class="accordion-body accordion-collapse collapse <?= !isset($list) ? 'show' : '' ?>" aria-labelledby="formHeader" data-bs-parent="#form">
                <div class="mb-3" id="speciesInput">
                  <label for="sp" class="form-label">Species</label>
                  <input type="text" class="form-control" name="sp" value="<?= $species ?? '' ?>">
                </div>
                <fieldset class="mb-3" id="sexSelect">
                  <div class="form-label">Description is for</div>
                  <ul class="list-unstyled mb-0">
                    <li class="form-check form-check-inline">
                      <?php //var_dump($_POST['sexRadio'] == 'male'); ?>
                      <input class="form-check-input" type="radio" name="sexRadio" id="femaleRadio" value="female" <?= (!isset($_POST['sexRadio']) || $_POST['sexRadio'] == 'female') ? 'checked' : '' ?>>
                      <label class="form-check-label" for="femaleRadio">Female</label>
                    </li>
                    <li class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sexRadio" id="maleRadio" value="male" <?= (isset($_POST['sexRadio']) && $_POST['sexRadio'] == 'male') ? 'checked' : '' ?>>
                      <label class="form-check-label" for="maleRadio">Male</label>
                    </li>
                  </ul>
                </fieldset>
                <div class="mb-3" id="descriptionInput">
                  <label for="desc" class="form-label">Description</label>
                  <textarea name="desc" class="form-control" rows="5" cols="80"><?= $orig_desc ?? '' ?></textarea>
                </div>
                <button type="submit" name="submit" class="btn btn-primary">Prettify</button>
              </fieldset>
            </div>
          </form>
          <?= $list ?? '' ?>
        </main>
        <!-- My personal usability preference is for info boxes like this to
        be persistent (rather than a modal overlay where I have to choose between
        viewing the page and viewing the text about the page) but also to not
        distractingly shift other text on the page around when opened. Most of
        the breakpoints are therefore about not letting this sidebar eat too
        much of the screen (because when it's collapsed, which is most of the
        time, it's just a big empty area) or get squeezed too small (because
        there are a lot of long words so it doesn't squish well).
        -->
        <aside class="col-md-4 col-xl order-1 order-md-3 ps-md-0 mb-2">
            <button class="btn bg-dark text-light rounded-0 rounded-bottom" id="aboutToggle" type="button" data-bs-toggle="collapse" data-bs-target="#aboutText" aria-expanded="false" aria-controls="aboutText" onclick="reshapeAboutButton()">?</button>
            <div class="collapse" id="aboutText">
              <div class="card card-body bg-light border border-1 border-dark rounded" id="aboutText">
                <p>This is a simple text parser for making insect species descriptions a bit more readable to the less-experienced eye. It breaks up a dense block of text into shorter chunks that will eventually be possible to further mark, edit, and rearrange.</p>
                <p>The reader was designed for one use case in particular: <a href="https://www.discoverlife.org/mp/20q?search=Apoidea">Discover Life’s bee pages</a> for eastern North America, which include species descriptions from Theodore B. Mitchell’s <i>Bees of the Eastern United States</i> (<a href="https://projects.ncsu.edu/cals/entomology/museum/easternBees.php">full text</a>) and sometimes other sources. It should be useful with other kinds of insect species descriptions too, as long as they’re structured in about the same way and use most of the same anatomical terms that bees do.</p>
                <p>To try the reader out on an arbitrary bee, click this button for the leaf-cutter bee <a href="https://www.discoverlife.org/20/q?search=Megachile+xylocopoides"><i>Megachile xylocopoides</i></a>:</p>
                <button class="btn btn-dark" type="button" name="sample" onclick="checkBeforeAutofill()">Autofill with sample</button>
              </div>
            </div>
        </aside>
      </div> <!-- row end -->
    </div> <!-- body container end -->
    <div class="modal fade" id="confirmResetModal" tabindex="-1" aria-labelledby="confirmResetLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Text has already been entered into the form. Are you sure you want to overwrite it with the sample text?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <button type="button" class="btn btn-primary" onclick="autofillSample()">Yes</button>
          </div>
        </div>
      </div>
    </div>
    <footer></footer>
  </body>
</html>
<script>
  const confirmModal = new bootstrap.Modal(document.getElementById('confirmResetModal'));

  function toggleHighlight(event) {
    if (!event) { event = window.event; }
    event.preventDefault();
    let icon = (event.target || event.srcElement);
    icon.classList.toggle('bi-bookmark-heart');
    icon.classList.toggle('bi-bookmark-heart-fill');
    // let text = icon.nextElementSibling.classList.toggle('fw-bold');
    let li = icon.parentNode;
    li.classList.toggle('list-group-item-success');
  }


  // Just restyles the bottom corners of the "?" tab when it's clicked on
  function reshapeAboutButton() {
    let sidebar = document.querySelector('#aboutToggle');
    sidebar.classList.toggle('rounded-bottom');
  }

  // Make sure user is aware this action will overwrite any existing text in the form
  function checkBeforeAutofill() {
    let speciesInput = document.getElementById('speciesInput').lastElementChild;
    let descriptionInput = document.getElementById('descriptionInput').lastElementChild;
    if (speciesInput.value !== '' || descriptionInput.textContent !== '') {
      confirmModal.toggle();
    }
    else autofillSample();
  }

  // ++make sure form is open and move focus to prettify button?
  function autofillSample() {
    const sampleSpecies = "Megachile xylocopoides";
    const sampleText = "Length 10-13 mm.; black, including tegulae and mid and hind legs in large part, front legs largely yellowish; eyes slightly convergent below; clypeal margin very slightly and broadly produced beneath the dense beard; mandibles 4-dentate (fig. 53), with a broad, sub-basal, triangular, inferior process; apical segment of flagellum somewhat flattened and dilated; lateral ocelli slightly nearer eyes than to margin of vertex; cheeks subequal to eyes in width, strongly narrowed and slightly grooved below base of mandible, posterior margin of this area carinate, a dense fringe of snow-white and rather elongate hairs just above the disc toward posterior margin; vertex shining, punctures quite deep and distinct, not very sparse, well separated in large part, but with an impunctate area between ocelli, and largely impunctate between ocelli and eyes; cheeks quite coarsely and deeply punctate above, becoming very densely and finely tessellate below; face below ocelli beneath dense pubescence very finely and closely rugoso-punctate; pubescence of face creamy-white, dense, copious and quite elongate, rather thin and blackish on vertex, short, thin and whitish on cheeks; pleura largely fuscous pubescent; scutum with erect but rather short and thin, black pubescence, becoming rather narrowly whitish anteriorly, scutellum and posterior face of propodeum with quite elongate, erect, whitish pubescence; punctures of scutum and scutellum quite deep and distinct, rather coarse, somewhat separated medially, becoming close laterally, crowded between notaulices and tegulae, slightly separated on axillae; pleura rather finely and densely rugose; lateral faces of propodeum very finely but rather closely punctate, posterior face somewhat more shining, with minute, close, rather shallow and vague punctures; front coxal spines elongate but rather narrow, subacute apically, each coxa with a small, inconspicuous patch of rather pale setae anterior to the spine, otherwise quite closely and deeply punctate, with a dense patch of pale pubescence laterally; mid tibial spurs absent; front tarsi pale yellow, very broadly dilated, segments 1 and 2 subequal in length on posterior margin, but basitarsus broadly expanded apically, very deeply excavated, overlying segment 2 nearly to its apex, this segment more narrowly produced anteriorly, posterior fringe rather short, hairs tipped with fuscous beneath; front tibia piceous on outer face, yellowish on the other faces, front femora piceous toward apex on posterior face, lower margin conspicuously carinate, otherwise pale yellowish, with a conspicuous, yellowish-white posterior fringe, lower basal margin angulate; mid tarsi slender and very much elongated, rather dark, with brownish-fuscous pubescence and a short, posterior fringe; hind basitarsus rather short, piceous, densely brownish pubescent beneath, forming rather conspicuous anterior and posterior fringes; tegulae very minutely and closely punctate; front wings quite deeply infuscated, hind wings more nearly subhyaline, veins brownish-piceous; abdominal terga 2-5 rather deeply depressed across base, basal margins of the grooves more or less distinctly carinate, apical margins rather deeply depressed laterally on 2 and 3, depression entire on 4 and 5, very deep and abrupt laterally; punctures minute and rather close on basal tergum, minute and well separated on 2 laterally, somewhat closer medially, somewhat deeper and more distinct on 3-5, well separated on 3 becoming closer on 4 and very close on 5; tergum 1 with erect, pale pubescence medially, becoming somewhat more fuscous laterally; discal pubescence of following terga short, erect and fuscous, apical fasciae entirely absent; tergum 6 completely vertical, very densely and finely rugoso-punctate, quite deeply depressed in center, the canna low but distinct, broadly and shallowly emarginate, the two resulting angles rather narrowly rounded, apical margin without visible teeth; tergum 7 largely hidden, very broadly and obtusely angulate; sterna 1-4 exposed, punctures rather close and fine in general, becoming rather minute and sparse toward margins of 2 and 3, rims very narrowly yellowish hyaline on 2 and 3, somewhat broader on 4; setose area of sternum 5 rather broadly out- curved apically, separated from basal margin of the plate by a rather broad, membraneous area, setae very fine and dense (fig. 54); setose areas of sternum 6 not completely separated medially, setae rather elongate and more sparse, apical lobe narrowly produced and rounded; gonocoxites robust, rather abruptly narrowed above base, apex conspicuously trilobate (figs. 50 & 55).";

    confirmModal.hide();

    let speciesInput = document.getElementById('speciesInput').lastElementChild;
    let descriptionInput = document.getElementById('descriptionInput').lastElementChild;
    speciesInput.value = sampleSpecies;
    descriptionInput.textContent = sampleText;
    // sample text is for male
    document.getElementById('maleRadio').checked = true;
  }
</script>
