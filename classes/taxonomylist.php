<?php
namespace Grav\Plugin;

use Grav\Common\GravTrait;

class Taxonomylist
{
    use GravTrait;

    /**
     * @var array
     */
    protected $taxonomylist;

    /**
     * Get taxonomy list.
     *
     * @return array
     */
    public function get()
    {
        if (!$this->taxonomylist) {
            $this->build();
        }
        return $this->taxonomylist;
    }

    /**
     * @internal
     */
    protected function build()
    {
        $taxonomylist = self::getGrav()['taxonomy']->taxonomy();
        $cache = self::getGrav()['cache'];
        $hash = hash('md5', serialize($taxonomylist));

        if ($taxonomy = $cache->fetch($hash)) {
            $this->taxonomylist = $taxonomy;
        } else {
            // get all slugs related to the 'category' named 'blog'
            // This map is structured as follows:
            // ['category']['category-value' (e.g. 'blog')][paths]['slug']
            $blog_slugs = [];
            foreach ($taxonomylist['category']['blog'] as $key => $value) {
                $blog_slugs[] = $value['slug'];
            }
            $blog_slugs = array_unique($blog_slugs);
            $newlist = [];
            foreach ($taxonomylist as $x => $y) {
                if ($x != 'tag') {
                    continue;
                }
                $partial = [];
                // This map is structured as follows
                // ['tag']['tag-value'] (e.g. 'grav')][paths]['slug']
                foreach ($taxonomylist[$x] as $key => $value) {
                    foreach ($value as $path => $slugs) {
                        if (in_array($slugs['slug'], $blog_slugs, TRUE)) {
                            $taxonomylist[$x][strval($key)] = count($value);
                            $partial[strval($key)] = count($value);
                        }
                    }
                }
                if (0 < count($partial)) {
                    arsort($partial);
                    $newlist[$x] = $partial;
                }
            }
            $cache->save($hash, $newlist);
            $this->taxonomylist = $newlist;
        }
    }
}
