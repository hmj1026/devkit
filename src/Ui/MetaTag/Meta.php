<?php

namespace Devkit\Ui\MetaTag;

use Butschster\Head\Packages\Entities\OpenGraphPackage;

/**
 * Weighted meta-tag manager wrapping butschster/meta-tags ^2.1 || ^3.0.
 *
 * Spec-derived contract (openspec/specs/devkit-blade-helpers/spec.md):
 *   - addStyle/addScript/addTag accept an integer $weight; lower weights
 *     render first, equal weights preserve insertion order (stable sort).
 *   - appendTitle(?string) is a no-op when text is null.
 *   - makeTitle() returns the composed title using the configured separator.
 *   - getOpenGraphPackage(name) returns an existing OpenGraphPackage or
 *     lazily creates one on first access.
 *
 * The underlying butschster class `Butschster\Head\Packages\Entities\OpenGraphPackage`
 * keeps the same namespace and constructor signature between v2 and v3, so
 * importing it directly is safe across the dual-version range.
 *
 * Storage for scripts/styles/tags is intentionally self-contained (not
 * delegated to butschster's Meta) because v2/v3 differ in how they expose
 * placement bags. The internal weighted collection guarantees identical
 * ordering semantics regardless of which butschster major Composer resolves.
 */
class Meta
{
    /**
     * Scripts grouped by placement; each entry is {name, src, attributes, weight, seq}.
     *
     * @var array<string, array<int, array>>
     */
    protected $scripts = array();

    /**
     * Styles grouped by placement.
     *
     * @var array<string, array<int, array>>
     */
    protected $styles = array();

    /**
     * Generic tags grouped by placement.
     *
     * @var array<string, array<int, array>>
     */
    protected $tags = array();

    /**
     * Lazy-created OpenGraph packages keyed by name.
     *
     * @var array<string, OpenGraphPackage>
     */
    protected $packages = array();

    /**
     * @var Title
     */
    protected $title;

    /**
     * Monotonic insertion sequence used as the stable-sort tiebreaker.
     *
     * @var int
     */
    protected $sequence = 0;

    public function __construct(Title $title = null)
    {
        $this->title = $title === null ? new Title() : $title;
    }

    /**
     * @return Title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Append a title segment. Null inputs are no-ops so optional view-layer
     * data can be passed through without conditional guards at the call site.
     *
     * @param  string|null  $text
     * @return $this
     */
    public function appendTitle($text)
    {
        if ($text === null) {
            return $this;
        }

        $this->title->append($text);

        return $this;
    }

    /**
     * @return string
     */
    public function makeTitle()
    {
        return $this->title->render();
    }

    /**
     * Register a stylesheet. Styles always live in the head placement.
     *
     * @param  string  $name
     * @param  string  $src
     * @param  array  $attributes
     * @param  int  $weight
     * @return $this
     */
    public function addStyle($name, $src, array $attributes = array(), $weight = 0)
    {
        $this->styles['head'][] = $this->buildEntry($name, $src, $attributes, $weight);

        return $this;
    }

    /**
     * Register a script. Defaults to the footer placement to match butschster
     * v2's convention; pass 'head' for blocking-load scripts.
     *
     * @param  string  $name
     * @param  string  $src
     * @param  array  $attributes
     * @param  string  $placement
     * @param  int  $weight
     * @return $this
     */
    public function addScript($name, $src, array $attributes = array(), $placement = 'footer', $weight = 0)
    {
        $this->scripts[$placement][] = $this->buildEntry($name, $src, $attributes, $weight);

        return $this;
    }

    /**
     * Register a generic tag. `$src` is null for tags that carry data via
     * attributes only (e.g. <meta name="..." content="...">).
     *
     * @param  string  $name
     * @param  array  $attributes
     * @param  string  $placement
     * @param  int  $weight
     * @return $this
     */
    public function addTag($name, array $attributes = array(), $placement = 'head', $weight = 0)
    {
        $this->tags[$placement][] = $this->buildEntry($name, null, $attributes, $weight);

        return $this;
    }

    /**
     * @param  string  $placement
     * @return array<int, array>
     */
    public function scriptsAt($placement)
    {
        return $this->sortedAt($this->scripts, $placement);
    }

    /**
     * @param  string  $placement
     * @return array<int, array>
     */
    public function stylesAt($placement = 'head')
    {
        return $this->sortedAt($this->styles, $placement);
    }

    /**
     * @param  string  $placement
     * @return array<int, array>
     */
    public function tagsAt($placement = 'head')
    {
        return $this->sortedAt($this->tags, $placement);
    }

    /**
     * Return an existing OpenGraph package by name, or lazily create one on
     * first access. The returned instance is butschster's OpenGraphPackage,
     * usable directly with both v2 and v3 of the underlying package.
     *
     * @param  string  $name
     * @return OpenGraphPackage
     */
    public function getOpenGraphPackage($name)
    {
        if (!isset($this->packages[$name])) {
            $this->packages[$name] = new OpenGraphPackage($name);
        }

        return $this->packages[$name];
    }

    /**
     * Drop every registered tag, script, style, package, and title segment.
     * Useful between requests in long-running workers and in tests.
     *
     * @return $this
     */
    public function reset()
    {
        $this->scripts = array();
        $this->styles = array();
        $this->tags = array();
        $this->packages = array();
        $this->title = new Title();
        $this->sequence = 0;

        return $this;
    }

    /**
     * @param  string  $name
     * @param  string|null  $src
     * @param  array  $attributes
     * @param  int  $weight
     * @return array
     */
    protected function buildEntry($name, $src, array $attributes, $weight)
    {
        return array(
            'name' => $name,
            'src' => $src,
            'attributes' => $attributes,
            'weight' => (int) $weight,
            'seq' => $this->sequence++,
        );
    }

    /**
     * Return the entries at a placement sorted by ascending weight, with
     * insertion sequence as the stable tiebreaker.
     *
     * @param  array  $bag
     * @param  string  $placement
     * @return array
     */
    protected function sortedAt(array $bag, $placement)
    {
        if (!isset($bag[$placement])) {
            return array();
        }

        $entries = $bag[$placement];

        usort($entries, function ($a, $b) {
            if ($a['weight'] === $b['weight']) {
                return $a['seq'] - $b['seq'];
            }

            return $a['weight'] - $b['weight'];
        });

        return $entries;
    }
}
