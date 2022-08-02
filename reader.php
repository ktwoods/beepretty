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
      'thorax','leg','tegula(e|)','coxa(e|)','fem(ur|oral|ora)','tibia(e|l|)','(basi|)tars(us|i|al)','wing','scut(um|al)','scutell(um|ar)'
    ),
    'abdomen' => array(
      'abdomen','terg(um|al|a|ite)','stern(um|al|a|ite)','fascia(e|)'
    )
  );
  // Convert anatomy lists into a regex search string for each region
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

    if ($orig_desc) { // If there's valid text left after sanitization:
      // Split into list items at semicolons
      $lines = explode('; ', $orig_desc);

      $next_region = 'head';
      foreach ($lines as $key => &$line) {
        // Capitalize the first letter and make it a list item
        $line = ($key === 0 ? '<h2>General</h2><ul><li>'.ucfirst($lines[0]).'</li>' : '<li>'.ucfirst($line).'</li>');
        // Subdivide description into head-thorax-abdomen
        /*
          Mitchell's descriptions are formatted in a reliable order (at least at the body segment level),
          so: check whether this li contains anatomy words from the next expected body segment; if so,
          we're done with the current body segment — close the list, add in a header with the next
          segment's name, and start a new list
        */
        if ($next_region && preg_match(${$next_region.'_regex'}, $line)) {
          $line = '</ul><h2>'.ucfirst($next_region).'</h2><ul>'.$line;
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
    <link rel="stylesheet" href="style.css">
    <!-- JavaScript Bundle with Popper -->
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
                <?php else: ?>
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
                <fieldset class="mb-3" id="sexSelectors">
                  <div class="form-label">Description is for</div>
                  <ul class="list-unstyled mb-0">
                    <li class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sexRadio" id="femaleRadio" value="female" checked>
                      <label class="form-check-label" for="femaleRadio">Female</label>
                    </li>
                    <li class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sexRadio" id="maleRadio" value="male">
                      <label class="form-check-label" for="maleRadio">Male</label>
                    </li>
                    <li class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sexRadio" id="bothRadio" disabled>
                      <label class="form-check-label" for="bothRadio">Both (WIP)</label>
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
          <div class="card" id="aboutCard">
            <div class="card-header">
              <button class="btn bg-dark text-light rounded-0 rounded-bottom" id="aboutToggle" type="button" data-bs-toggle="collapse" data-bs-target="#aboutText" aria-expanded="false" aria-controls="aboutText" onclick="reshapeAboutButton()">?</button>
            </div>
            <div class="card-body collapse bg-light border border-1 border-dark rounded" id="aboutText">
              <p>This is a simple text parser for making insect species descriptions a bit more readable to the less-experienced eye. It breaks up a dense block of text into shorter chunks that will eventually be possible to further mark, edit, and rearrange.</p>
              <p>The reader was designed for one use case in particular: <a href="https://www.discoverlife.org/mp/20q?search=Apoidea">Discover Life’s bee pages</a> for eastern North America, which include species descriptions from Theodore B. Mitchell’s <i>Bees of the Eastern United States</i> (<a href="https://projects.ncsu.edu/cals/entomology/museum/easternBees.php">full text</a>) and sometimes other sources. It should be useful with other kinds of insect species descriptions too, as long as they’re structured in about the same way and use most of the same anatomical terms that bees do.</p>
              <p>To try the reader out on an arbitrary bee, click this button for the leaf-cutter bee <i>Megachile xylocopoides</i>:</p>
              <button class="btn btn-dark" type="button" name="sample" onclick="autofillSample()">Autofill with sample</button>
            </div>
          </div>
        </aside>
      </div> <!-- row end -->
    </div> <!-- body container end -->
    <footer></footer>
  </body>
</html>
<script>


  function reshapeAboutButton() {
    let sidebar = document.querySelector('#aboutToggle');
    sidebar.classList.toggle('rounded-bottom');
  }

  function autofillSample() {
    // if there's already text in the form, check that user definitely wants to overwrite it first
    // if so, populate values

    let form = document.querySelector('form');
    // form. .value = sampleSpecies;
    // form. .toggleAttribute('checked');
    // form. .textContent = sampleText;
    // put focus on prettify button?

    const sampleSpecies = "Megachile xylocopoides";
    const sampleSex = "female";
    const sampleText = "Length 14-15 mm.; entirely black, including tegulae and legs, mid and hind spurs brownish-testaceous; eyes very slightly convergent below; clypeal margin broadly and very slightly incurved; mandibles 5-dentate, a long bevelled edge between 2nd and 3rd teeth (fig. 53); lateral ocelli slightly nearer eyes than to margin of vertex; cheeks slightly narrower than eyes; vertex shining, rather irregularly and sparsely punctate, punctures becoming somewhat coarser toward margin, but with an impunctate area between ocelli, and punctures very minute, sparse and obscure between ocelli and eyes; cheeks becoming quite coarsely and closely punctate posteriorly and below, very finely and closely punctate toward eyes; face below anterior ocellus quite distinctly and deeply punctate punctures well separated, becoming somewhat finer and densely crowded laterally and along inner margins to clypeus, supraclypeal area shining, with only a few, scattered, rather coarse and deep punctures, clypeus also shining, punctures quite close, deep and irregular, becoming somewhat finer toward the slightly elevated and impunctate apical margin; pubescence of entire head and thorax black to deep fuscous, rather short, quite dense around antennae, on cheeks below, and quite copious on thorax laterally and posteriorly; scutum and scutellum somewhat shining, punctures rather fine but quite deep and distinct, sparse over most of scutum, becoming rather close anteriorly, and between notaulices and tegulae; scutellum broadly angulate, punctures sparse medially, but becoming quite close laterally, somewhat finer and closer on axillae; pleura densely and rather finely punctate; lateral faces of propodeum finely rugoso-punctate, posterior face smoother and more shining, with fine, obscure, shallow and quite irregular punctures; basitarsi of all legs nearly as broad and only slightly shorter than their respective tibiae; tegulae shining, very minutely and closely punctate; front wings deep fuliginous, hind wings somewhat less so, veins brownish piceous; abdominal terga only slightly depressed basally, basal margins of the depressions evident only on 2 and 3, apical margins of the terga deeply depressed laterally, fasciae absent; discal pubescence very short and obscure, suberect, entirely fuscous, somewhat more copious and elongate on basal tergum laterally; punctures of terga fine but distinct, rather uniformly close across basal tergum, becoming more sparse on the more apical terga, depressed margins more deeply, closely and finely punctate; tergum 6 nearly straight in profile, with no erect hairs visible, quite densely covered with fuscous, appressed tomentum, the close and fine punctures visible only at extreme sides of base; sternum 6 bare over apical half, with a bare apical lip projecting slightly beyond the dense, subapical fringe of short, fuscous hairs; scopa entirely black, punctures of sterna rather fine and close, becoming slightly coarser to sternum 5, apical margins of plates very narrowly hyaline, sternal fasciae entirely absent.";
  }
</script>
