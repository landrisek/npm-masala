package masala

import ("github.com/jinzhu/configor"
	"gopkg.in/mgo.v2"
	"time"
)

type MongoBuilder struct {
	Config struct {
		Mongo struct {
			Host string
			Name string
			Password string
			Port int
			User string
		}
		Tables map[string]string
	}
}

func (builder MongoBuilder) Inject() (MongoBuilder, *mgo.Database) {
	configor.Load(&builder.Config, "../config.yml")
	info := &mgo.DialInfo{
		Addrs:    []string{builder.Config.Mongo.Host},
		Timeout:  60 * time.Second,
		Database: builder.Config.Mongo.Name,
		Username: builder.Config.Mongo.User,
		Password: builder.Config.Mongo.Password,
	}
	session, err := mgo.DialWithInfo(info)
	if err != nil {
		panic(err)
	}
	return builder, session.DB(builder.Config.Mongo.Name)
}